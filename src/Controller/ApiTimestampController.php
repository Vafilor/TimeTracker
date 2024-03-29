<?php

declare(strict_types=1);

namespace App\Controller;

use App\Api\ApiFormError;
use App\Api\ApiPagination;
use App\Api\ApiProblem;
use App\Api\ApiProblemException;
use App\Api\ApiStatisticValue;
use App\Api\ApiTimestamp;
use App\Entity\TagLink;
use App\Entity\Timestamp;
use App\Form\EditTimestampFormType;
use App\Form\Model\EditTimestampModel;
use App\Manager\TagManager;
use App\Manager\TimestampManager;
use App\Repository\StatisticRepository;
use App\Repository\StatisticValueRepository;
use App\Repository\TagLinkRepository;
use App\Repository\TagRepository;
use App\Repository\TimestampRepository;
use App\Traits\HasStatisticDataTrait;
use App\Traits\TaggableController;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Knp\Bundle\TimeBundle\DateTimeFormatter;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApiTimestampController extends BaseController
{
    use TaggableController;

    use HasStatisticDataTrait;

    public function __construct(private DateTimeFormatter $dateTimeFormatter)
    {
    }

    #[Route('/api/timestamp', name: 'api_timestamp_index', methods: ['GET'])]
    public function index(
        Request $request,
        TimestampRepository $timestampRepository,
        PaginatorInterface $paginator,
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        // TODO add filter
        $queryBuilder = $timestampRepository->findByUserQueryBuilder($this->getUser());
        $queryBuilder = $timestampRepository->preloadTags($queryBuilder);

        $pagination = $this->populatePaginationData($request, $paginator, $queryBuilder, [
            'sort' => 'timestamp.createdAt',
            'direction' => 'desc',
        ]);

        $items = ApiTimestamp::fromEntities($pagination->getItems(), $this->dateTimeFormatter, $this->getUser(), $this->now());

        return $this->jsonNoNulls(ApiPagination::fromPagination($pagination, $items));
    }

    #[Route('/api/timestamp', name: 'api_timestamp_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        TagManager $tagManager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $now = $this->now();
        $timestamp = new Timestamp($this->getUser());

        $data = $this->getJsonBody($request, []);
        if (array_key_exists('tags', $data)) {
            $tagNames = $tagManager->parseFromString($data['tags']);
            // TODO Also add this to time entries
            $tagObjects = $tagManager->findOrCreateByNames($tagNames, $this->getUser());
            foreach ($tagObjects as $tag) {
                $tagLink = new TagLink($timestamp, $tag);
                $timestamp->addTagLink($tagLink);
                $entityManager->persist($tagLink);
            }
        }

        $entityManager->persist($timestamp);
        $entityManager->flush();

        $apiTimestamp = ApiTimestamp::fromEntity($this->dateTimeFormatter, $timestamp, $this->getUser(), $now);

        if (str_starts_with($request->getPathInfo(), '/json')) {
            $response = [
                'timestamp' => $apiTimestamp,
                'view' => $this->renderView('timestamp/partials/_timestamp.html.twig', ['timestamp' => $timestamp]),
            ];

            return $this->jsonNoNulls($response, Response::HTTP_CREATED);
        }

        return $this->jsonNoNulls($apiTimestamp, Response::HTTP_CREATED);
    }

    #[Route('/api/timestamp/{id}', name: 'api_timestamp_view', methods: ['GET'])]
    public function view(TimestampRepository $timestampRepository, string $id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $queryBuilder = $timestampRepository->findCreateQueryBuilder($id);

        /** @var Timestamp|null $timestamp */
        $timestamp = $timestampRepository->preloadTags($queryBuilder)->getQuery()->getResult();
        if (is_null($timestamp)) {
            $this->createNotFoundException();
        }

        if (!$timestamp->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $apiTimestamp = ApiTimestamp::fromEntity($this->dateTimeFormatter, $timestamp, $this->getUser(), $this->now());

        return $this->jsonNoNulls($apiTimestamp);
    }

    #[Route('/api/timestamp/{id}', name: 'api_timestamp_edit', methods: ['PUT'])]
    #[Route('/json/timestamp/{id}', name: 'json_timestamp_update', methods: ['PUT'])]
    public function edit(
        Request $request,
        EntityManagerInterface $entityManager,
        TimestampRepository $timestampRepository,
        string $id
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $timestamp = $timestampRepository->findOrException($id);
        if (!$timestamp->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(EditTimestampFormType::class, EditTimestampModel::fromEntity($timestamp), [
            'timezone' => $this->getUser()->getTimezone(),
            'csrf_protection' => false,
        ]);

        $data = $this->getJsonBody($request);

        try {
            $form->submit($data);
        } catch (InvalidArgumentException) {
            throw new ApiProblemException(new ApiProblem(Response::HTTP_BAD_REQUEST, ApiProblem::TYPE_VALIDATION_ERROR));
        }

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var EditTimestampModel $data */
            $data = $form->getData();

            $timestamp->setDescription($data->getDescription());

            $entityManager->flush();
        } elseif (!$form->isValid()) {
            $formError = new ApiFormError($form->getErrors(true));
            throw new ApiProblemException($formError);
        }

        $apiTimestamp = ApiTimestamp::fromEntity($this->dateTimeFormatter, $timestamp, $this->getUser(), $this->now());

        return $this->jsonNoNulls($apiTimestamp);
    }

    #[Route('/api/timestamp/{id}/delete', name: 'api_timestamp_delete', methods: ['DELETE'])]
    public function remove(
        TimestampRepository $timestampRepository,
        EntityManagerInterface $entityManager,
        string $id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $timestamp = $timestampRepository->findOrException($id);
        if (!$timestamp->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $entityManager->remove($timestamp);
        $entityManager->flush();

        return $this->jsonNoContent();
    }

    #[Route('/api/timestamp/{id}/repeat', name: 'api_timestamp_repeat', methods: ['POST'])]
    #[Route('/json/timestamp/{id}/repeat', name: 'json_timestamp_repeat', methods: ['POST'])]
    public function repeat(
        Request $request,
        EntityManagerInterface $entityManager,
        DateTimeFormatter $dateTimeFormatter,
        TimestampRepository $timestampRepository,
        TimestampManager $timestampManager,
        string $id
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $timestamp = $timestampRepository->findOrException($id);
        if (!$timestamp->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $newTimestamp = $timestampManager->repeat($timestamp);

        $entityManager->flush();

        $apiTimestamp = ApiTimestamp::fromEntity(
            $dateTimeFormatter,
            $newTimestamp,
            $this->getUser(),
            $this->now()
        );

        if (str_starts_with($request->getPathInfo(), '/json')) {
            $response = [
                'timestamp' => $apiTimestamp,
                'view' => $this->renderView('timestamp/partials/_timestamp.html.twig', ['timestamp' => $newTimestamp]),
            ];

            return $this->jsonNoNulls($response, Response::HTTP_CREATED);
        }

        return $this->jsonNoNulls($apiTimestamp, Response::HTTP_CREATED);
    }

    #[Route('/api/timestamp/{id}/tag', name: 'api_timestamp_tag_create', methods: ['POST'])]
    #[Route('/json/timestamp/{id}/tag', name: 'json_timestamp_tag_create', methods: ['POST'])]
    public function addTag(
        Request $request,
        EntityManagerInterface $entityManager,
        TimestampRepository $timestampRepository,
        TagManager $tagManager,
        TagLinkRepository $tagLinkRepository,
        string $id
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $timestamp = $timestampRepository->findOrException($id);
        if (!$timestamp->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        return $this->addTagRequest(
            $request,
            $entityManager,
            $tagManager,
            $tagLinkRepository,
            $this->getUser(),
            $timestamp
        );
    }

    #[Route('/api/timestamp/{id}/tag/{tagName}', name: 'api_timestamp_tag_delete', methods: ['DELETE'])]
    #[Route('/json/timestamp/{id}/tag/{tagName}', name: 'json_timestamp_tag_delete', methods: ['DELETE'])]
    public function removeTag(
        EntityManagerInterface $entityManager,
        TimestampRepository $timestampRepository,
        TagRepository $tagRepository,
        TagLinkRepository $tagLinkRepository,
        string $id,
        string $tagName
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $timestamp = $timestampRepository->findOrException($id);
        if (!$timestamp->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        return $this->removeTagRequest(
            $entityManager,
            $tagRepository,
            $tagLinkRepository,
            $this->getUser(),
            $tagName,
            $timestamp
        );
    }

    #[Route('/api/timestamp/{id}/tags', name: 'api_timestamp_tags', methods: ['GET'])]
    #[Route('/json/timestamp/{id}/tags', name: 'json_timestamp_tags', methods: ['GET'])]
    public function indexTag(
        TimestampRepository $timestampRepository,
        string $id
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $timestamp = $timestampRepository->findOrException($id);
        if (!$timestamp->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        return $this->getTagsRequest($timestamp);
    }

    #[Route('/api/timestamp/{id}/statistic', name: 'api_timestamp_statistic_create', methods: ['POST'])]
    #[Route('/json/timestamp/{id}/statistic', name: 'json_timestamp_statistic_create', methods: ['POST'])]
    public function addStatisticValue(
        Request $request,
        EntityManagerInterface $entityManager,
        TimestampRepository $timestampRepository,
        StatisticRepository $statisticRepository,
        StatisticValueRepository $statisticValueRepository,
        string $id
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $timestamp = $timestampRepository->findOrException($id);
        if (!$timestamp->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $statisticValue = $this->addStatisticValueRequest(
            $request,
            $entityManager,
            $statisticRepository,
            $statisticValueRepository,
            $this->getUser(),
            $timestamp
        );

        $apiStatisticValue = ApiStatisticValue::fromEntity($statisticValue, $this->getUser());
        if (str_starts_with($request->getPathInfo(), '/json')) {
            $response = [
                'statisticValue' => $apiStatisticValue,
                'view' => $this->renderView('statistic_value/partials/_statistic-value.html.twig', ['value' => $statisticValue]),
            ];

            return $this->jsonNoNulls($response, Response::HTTP_CREATED);
        }

        return $this->jsonNoNulls($apiStatisticValue, Response::HTTP_CREATED);
    }

    #[Route('/api/timestamp/{id}/statistic/{statisticId}', name: 'api_timestamp_statistic_delete', methods: ['DELETE'])]
    #[Route('/json/timestamp/{id}/statistic/{statisticId}', name: 'json_timestamp_statistic_delete', methods: ['DELETE'])]
    public function removeStatisticValue(
        EntityManagerInterface $entityManager,
        TimestampRepository $timestampRepository,
        StatisticValueRepository $statisticValueRepository,
        string $id,
        string $statisticId,
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $timestamp = $timestampRepository->findOrException($id);
        if (!$timestamp->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        return $this->removeStatisticValueRequest($entityManager, $statisticValueRepository, $statisticId);
    }
}

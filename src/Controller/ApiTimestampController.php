<?php

declare(strict_types=1);

namespace App\Controller;

use App\Api\ApiError;
use App\Api\ApiFormError;
use App\Api\ApiPagination;
use App\Api\ApiProblem;
use App\Api\ApiProblemException;
use App\Api\ApiStatisticValue;
use App\Api\ApiTag;
use App\Api\ApiTimestamp;
use App\Entity\Statistic;
use App\Entity\StatisticValue;
use App\Entity\Tag;
use App\Entity\TagLink;
use App\Entity\Timestamp;
use App\Form\AddStatisticFormType;
use App\Form\AddStatisticValueFormType;
use App\Form\Model\AddStatisticValue;
use App\Manager\TagManager;
use App\Manager\TimestampManager;
use App\Repository\StatisticRepository;
use App\Repository\StatisticValueRepository;
use App\Repository\TagLinkRepository;
use App\Repository\TagRepository;
use App\Repository\TimestampRepository;
use InvalidArgumentException;
use Knp\Bundle\TimeBundle\DateTimeFormatter;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApiTimestampController extends BaseController
{
    private DateTimeFormatter $dateTimeFormatter;

    public function __construct(DateTimeFormatter $dateTimeFormatter)
    {
        $this->dateTimeFormatter = $dateTimeFormatter;
    }

    #[Route('/api/timestamp', name: 'api_timestamp_index', methods: ["GET"])]
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
            'direction' => 'desc'
        ]);

        $items = ApiTimestamp::fromEntities($pagination->getItems(), $this->dateTimeFormatter, $this->getUser(), $this->now());

        return $this->jsonNoNulls(ApiPagination::fromPagination($pagination, $items));
    }

    #[Route('/api/timestamp', name: 'api_timestamp_create', methods: ["POST"])]
    public function create(Request $request, TagManager $tagManager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $now = $this->now();
        $timestamp = new Timestamp($this->getUser());

        $manager = $this->getDoctrine()->getManager();

        $data = $this->getJsonBody($request, []);
        if (array_key_exists('tags', $data)) {
            $tagNames = $tagManager->parseFromString($data['tags']);
            // TODO Also add this to time entries
            $tagObjects = $tagManager->findOrCreateByNames($tagNames, $this->getUser());
            foreach ($tagObjects as $tag) {
                $tagLink = new TagLink($timestamp, $tag);
                $timestamp->addTagLink($tagLink);
                $manager->persist($tagLink);
            }
        }

        $manager->persist($timestamp);
        $manager->flush();

        $apiTimestamp = ApiTimestamp::fromEntity($this->dateTimeFormatter, $timestamp, $this->getUser(), $now);

        return $this->jsonNoNulls($apiTimestamp, Response::HTTP_CREATED);
    }

    #[Route('/api/timestamp/{id}', name: 'api_timestamp_view', methods: ["GET"])]
    public function view(
        Request $request,
        TimestampRepository $timestampRepository,
        string $id
    ): Response {
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

    #[Route('/api/timestamp/{id}/delete', name: 'api_timestamp_delete', methods: ["DELETE"])]
    public function remove(
        Request $request,
        TimestampRepository $timestampRepository,
        string $id
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $timestamp = $timestampRepository->findOrException($id);
        if (!$timestamp->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $manager = $this->getDoctrine()->getManager();
        $manager->remove($timestamp);
        $manager->flush();

        return $this->jsonNoContent();
    }

    #[Route('/api/timestamp/{id}/repeat', name: 'api_timestamp_repeat', methods: ['POST'])]
    #[Route('/json/timestamp/{id}/repeat', name: 'json_timestamp_repeat', methods: ['POST'])]
    public function repeat(
        Request $request,
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

        $now = $this->now();

        $newTimestamp = $timestampManager->repeat($timestamp);

        $this->getDoctrine()->getManager()->flush();

        $apiTimestamp = ApiTimestamp::fromEntity(
            $dateTimeFormatter,
            $newTimestamp,
            $this->getUser(),
            $now
        );

        return $this->jsonNoNulls($apiTimestamp, Response::HTTP_CREATED);
    }

    #[Route('/api/timestamp/{id}/tag', name: 'api_timestamp_tag_create', methods: ['POST'])]
    #[Route('/json/timestamp/{id}/tag', name: 'json_timestamp_tag_create', methods: ['POST'])]
    public function addTag(
        Request $request,
        TimestampRepository $timestampRepository,
        TagRepository $tagRepository,
        TagLinkRepository $tagLinkRepository,
        string $id
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $timestamp = $timestampRepository->findOrException($id);
        if (!$timestamp->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $data = $this->getJsonBody($request);
        if (!array_key_exists('tagName', $data)) {
            $problem = ApiProblem::withErrors(
                Response::HTTP_BAD_REQUEST,
                ApiProblem::TYPE_INVALID_REQUEST_BODY,
                ApiError::missingProperty('tagName')
            );

            throw new ApiProblemException(
                $problem
            );
        }

        $tagName = $data['tagName'];

        $tag = $tagRepository->findOneBy(['name' => $tagName, 'assignedTo' => $this->getUser()]);
        if (is_null($tag)) {
            $tag = new Tag($this->getUser(), $tagName);
            $this->getDoctrine()->getManager()->persist($tag);
        }

        $exitingLink = $tagLinkRepository->findOneBy([
                                                              'timestamp' => $timestamp,
                                                              'tag' => $tag
                                                          ]);

        if (!is_null($exitingLink)) {
            return $this->json([], Response::HTTP_CONFLICT);
        }

        $tagLink = new TagLink($timestamp, $tag);
        $manager = $this->getDoctrine()->getManager();
        $manager->persist($tagLink);
        $manager->flush();

        $apiTag = ApiTag::fromEntity($tag);

        return $this->jsonNoNulls($apiTag, Response::HTTP_CREATED);
    }

    #[Route('/api/timestamp/{id}/tag/{tagName}', name: 'api_timestamp_tag_delete', methods: ['DELETE'])]
    #[Route('/json/timestamp/{id}/tag/{tagName}', name: 'json_timestamp_tag_delete', methods: ['DELETE'])]
    public function removeTag(
        Request $request,
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

        $tag = $tagRepository->findOneByOrException(['name' => $tagName]);

        $exitingLink = $tagLinkRepository->findOneBy([
                                                              'timestamp' => $timestamp,
                                                              'tag' => $tag
                                                          ]);

        if (is_null($exitingLink)) {
            throw new ApiProblemException(
                ApiProblem::invalidAction(
                    TimestampController::codeTagNotAssociated,
                    "Tag '$tagName' is not associated to this timestamp"
                )
            );
        }

        $manager = $this->getDoctrine()->getManager();
        $manager->remove($exitingLink);
        $manager->flush();

        return $this->jsonNoContent();
    }

    #[Route('/api/timestamp/{id}/tags', name: 'api_timestamp_tags', methods: ["GET"])]
    #[Route('/json/timestamp/{id}/tags', name: 'json_timestamp_tags', methods: ["GET"])]
    public function indexTag(
        Request $request,
        TimestampRepository $timestampRepository,
        string $id
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $timestamp = $timestampRepository->findOrException($id);
        if (!$timestamp->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        return $this->jsonNoNulls(ApiTag::fromEntities($timestamp->getTags()));
    }

    #[Route('/api/timestamp/{id}/statistic', name: 'api_timestamp_statistic_create', methods: ['POST'])]
    #[Route('/json/timestamp/{id}/statistic', name: 'json_timestamp_statistic_create', methods: ['POST'])]
    public function addStatisticValue(
        Request $request,
        TimestampRepository $timestampRepository,
        StatisticRepository $statisticRepository,
        string $id
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $timestamp = $timestampRepository->findOrException($id);
        if (!$timestamp->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(AddStatisticValueFormType::class, new AddStatisticValue(), [
            'csrf_protection' => false
        ]);

        $data = $this->getJsonBody($request);

        try {
            $form->submit($data);
        } catch (InvalidArgumentException $invalidArgumentException) {
            throw new ApiProblemException(new ApiProblem(Response::HTTP_BAD_REQUEST, ApiProblem::TYPE_VALIDATION_ERROR));
        }

        if (!$form->isSubmitted()) {
            throw new ApiProblemException(
                ApiFormError::invalidAction('bad_data', 'Form not submitted')
            );
        }

        if (!$form->isValid()) {
            $formError = new ApiFormError($form->getErrors(true));
            throw new ApiProblemException($formError);
        }

        /** @var AddStatisticValue $data */
        $data = $form->getData();
        $name = $data->getStatisticName();
        $value = $data->getValue();

        $statistic = $statisticRepository->findOneBy(['canonicalName' => $name, 'assignedTo' => $this->getUser()]);
        if (is_null($statistic)) {
            $statistic = new Statistic($this->getUser(), $name);
            $this->getDoctrine()->getManager()->persist($statistic);
        }

        $statisticValue = StatisticValue::fromTimestamp($statistic, $value, $timestamp);

        $manager = $this->getDoctrine()->getManager();
        $manager->persist($statisticValue);
        $manager->flush();

        $apiModel = ApiStatisticValue::fromEntity($statisticValue);

        return $this->jsonNoNulls($apiModel, Response::HTTP_CREATED);
    }

    #[Route('/api/timestamp/{id}/statistic/{statisticId}', name: 'api_timestamp_statistic_delete', methods: ['DELETE'])]
    #[Route('/json/timestamp/{id}/statistic/{statisticId}', name: 'json_timestamp_statistic_delete', methods: ['DELETE'])]
    public function removeStatisticValue(
        Request $request,
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

        $statisticValue = $statisticValueRepository->findOrException($statisticId);

        $manager = $this->getDoctrine()->getManager();
        $manager->remove($statisticValue);
        $manager->flush();

        return $this->jsonNoContent();
    }
}

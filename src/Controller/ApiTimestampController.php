<?php

declare(strict_types=1);

namespace App\Controller;

use App\Api\ApiError;
use App\Api\ApiPagination;
use App\Api\ApiProblem;
use App\Api\ApiProblemException;
use App\Api\ApiTag;
use App\Api\ApiTimestamp;
use App\Entity\Tag;
use App\Entity\Timestamp;
use App\Entity\TimestampTag;
use App\Manager\TagManager;
use App\Manager\TimestampManager;
use App\Repository\TagRepository;
use App\Repository\TimestampRepository;
use App\Repository\TimestampTagRepository;
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

        return $this->json(ApiPagination::fromPagination($pagination, $items));
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
            // Also add this to time entries
            $tagObjects = $tagManager->findOrCreateByNames($tagNames);
            foreach ($tagObjects as $tag) {
                $tagLink = new TimestampTag($timestamp, $tag);
                $timestamp->addTimestampTag($tagLink);
                $manager->persist($tagLink);
            }
        }

        $manager->persist($timestamp);
        $manager->flush();

        $apiTimestamp = ApiTimestamp::fromEntity($this->dateTimeFormatter, $timestamp, $this->getUser(), $now);

        return $this->json($apiTimestamp, Response::HTTP_CREATED);
    }

    #[Route('/api/timestamp/{id}', name: 'api_timestamp_view', methods: ["GET"])]
    public function view(
        Request $request,
        TimestampRepository $timestampRepository,
        string $id
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $queryBuilder = $timestampRepository->findCreateQueryBuilder($id);
        $timestamp = $timestampRepository->preloadTags($queryBuilder)->getQuery()->getResult();
        if (is_null($timestamp)) {
            $this->createNotFoundException();
        }

        if (!$timestamp->wasCreatedBy($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $apiTimestamp = ApiTimestamp::fromEntity($this->dateTimeFormatter, $timestamp, $this->getUser(), $this->now());

        return $this->json($apiTimestamp);
    }

    #[Route('/api/timestamp/{id}/delete', name: 'api_timestamp_delete', methods: ["DELETE"])]
    public function remove(
        Request $request,
        TimestampRepository $timestampRepository,
        string $id
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $timestamp = $timestampRepository->findOrException($id);
        if (!$timestamp->wasCreatedBy($this->getUser())) {
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
        if (!$timestamp->wasCreatedBy($this->getUser())) {
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

        return $this->json($apiTimestamp, Response::HTTP_CREATED);
    }

    #[Route('/api/timestamp/{id}/tag', name: 'api_timestamp_tag_create', methods: ['POST'])]
    #[Route('/json/timestamp/{id}/tag', name: 'json_timestamp_tag_create', methods: ['POST'])]
    public function addTag(
        Request $request,
        TimestampRepository $timestampRepository,
        TagRepository $tagRepository,
        TimestampTagRepository $timestampTagRepository,
        string $id
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $timestamp = $timestampRepository->findOrException($id);
        if (!$timestamp->wasCreatedBy($this->getUser())) {
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

        $tag = $tagRepository->findOneBy(['name' => $tagName, 'createdBy' => $this->getUser()]);
        if (is_null($tag)) {
            $tag = new Tag($this->getUser(), $tagName);
            $this->getDoctrine()->getManager()->persist($tag);
        }

        $exitingLink = $timestampTagRepository->findOneBy([
                                                              'timestamp' => $timestamp,
                                                              'tag' => $tag
                                                          ]);

        if (!is_null($exitingLink)) {
            return $this->json([], Response::HTTP_CONFLICT);
        }

        $timestampTag = new TimestampTag($timestamp, $tag);
        $manager = $this->getDoctrine()->getManager();
        $manager->persist($timestampTag);
        $manager->flush();

        $apiTag = ApiTag::fromEntity($tag);

        return $this->json($apiTag, Response::HTTP_CREATED);
    }

    #[Route('/api/timestamp/{id}/tag/{tagName}', name: 'api_timestamp_tag_delete', methods: ['DELETE'])]
    #[Route('/json/timestamp/{id}/tag/{tagName}', name: 'json_timestamp_tag_delete', methods: ['DELETE'])]
    public function removeTag(
        Request $request,
        TimestampRepository $timestampRepository,
        TagRepository $tagRepository,
        TimestampTagRepository $timestampTagRepository,
        string $id,
        string $tagName
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $timestamp = $timestampRepository->findOrException($id);
        if (!$timestamp->wasCreatedBy($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $tag = $tagRepository->findOneByOrException(['name' => $tagName]);

        $exitingLink = $timestampTagRepository->findOneBy([
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
        TimestampTagRepository $timestampTagRepository,
        string $id
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $timestamp = $timestampRepository->findOrException($id);
        if (!$timestamp->wasCreatedBy($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $timestampTags = $timestampTagRepository->findBy(['timestamp' => $timestamp]);

        return $this->json(ApiTag::fromEntities($timestampTags));
    }
}

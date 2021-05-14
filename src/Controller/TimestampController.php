<?php

declare(strict_types=1);

namespace App\Controller;

use App\Api\ApiError;
use App\Api\ApiProblem;
use App\Api\ApiProblemException;
use App\Api\ApiTag;
use App\Api\ApiTimestamp;
use App\Entity\Tag;
use App\Entity\Timestamp;
use App\Entity\TimestampTag;
use App\Form\Model\TimestampEditModel;
use App\Form\TimestampEditFormType;
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

class TimestampController extends BaseController
{
    const codeTagNotAssociated = 'tag_not_associated';

    #[Route('/timestamp', name: 'timestamp_index')]
    public function index(
        Request $request,
        TimestampRepository $timestampRepository,
        PaginatorInterface $paginator
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        // TODO add filter
        $queryBuilder = $timestampRepository->findByUserQueryBuilder($this->getUser());
        $queryBuilder = $timestampRepository->preloadTags($queryBuilder);

        $pagination = $this->populatePaginationData($request, $paginator, $queryBuilder, [
            'sort' => 'timestamp.createdAt',
            'direction' => 'desc'
        ]);

        return $this->render('timestamp/index.html.twig', [
            'pagination' => $pagination
        ]);
    }

    #[Route('/timestamp/create', name: 'timestamp_create')]
    public function create(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $timestamp = new Timestamp($this->getUser());

        $this->getDoctrine()->getManager()->persist($timestamp);
        $this->getDoctrine()->getManager()->flush();

        return $this->redirectToRoute('timestamp_view', ['id' => $timestamp->getIdString()]);
    }

    #[Route('/timestamp/{id}/view', name: 'timestamp_view')]
    public function view(
        Request $request,
        TimestampRepository $timestampRepository,
        TimestampTagRepository $timestampTagRepository,
        string $id
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $timestamp = $timestampRepository->findOrException($id);
        if (!$timestamp->wasCreatedBy($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(TimestampEditFormType::class, TimestampEditModel::fromEntity($timestamp), [
            'timezone' => $this->getUser()->getTimezone(),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var TimestampEditModel $data */
            $data = $form->getData();

            $timestamp->setCreatedAt($data->getCreatedAt());

            $this->getDoctrine()->getManager()->flush();

            $this->addFlash('success', 'Updated timestamp');
        }

        $timestampTags = $timestampTagRepository->findBy(['timestamp' => $timestamp]);
        $apiTags = array_map(
            fn ($timestampTag) => ApiTag::fromEntity($timestampTag->getTag()),
            $timestampTags
        );

        return $this->render('timestamp/view.html.twig', [
            'form' => $form->createView(),
            'timestamp' => $timestamp,
            'tags' => $apiTags
        ]);
    }

    #[Route('/timestamp/{id}/delete', name: 'timestamp_delete')]
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

        $this->addFlash('success', 'Timestamp was removed');

        return $this->redirectToRoute('timestamp_index');
    }

    #[Route('/json/timestamp/{id}/repeat', name: 'timestamp_json_repeat', methods: ['POST'])]
    public function jsonRepeat(
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

    #[Route('/json/timestamp/{id}/tag', name: 'timestamp_json_tag_create', methods: ['POST'])]
    public function jsonAddTag(
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

    #[Route('/json/timestamp/{id}/tag/{tagName}', name: 'timestamp_json_tag_delete', methods: ['DELETE'])]
    public function jsonDeleteTag(
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
                    self::codeTagNotAssociated,
                    "Tag '$tagName' is not associated to this timestamp"
                )
            );
        }

        $manager = $this->getDoctrine()->getManager();
        $manager->remove($exitingLink);
        $manager->flush();

        return $this->jsonNoContent();
    }

    #[Route('/json/timestamp/{id}/tags', name: 'timestamp_json_tags')]
    public function jsonTags(
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

        $apiTags = array_map(
            fn ($timestampTag) => ApiTag::fromEntity($timestampTag->getTag()),
            $timestampTags
        );

        return $this->json($apiTags);
    }
}

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
        $queryBuilder = $timestampRepository->findCreateQueryBuilder($id);
        $timestampRepository->preloadTags($queryBuilder);

        /** @var Timestamp|null $timestamp */
        $timestamp = $queryBuilder->getQuery()->getOneOrNullResult();
        if (is_null($timestamp)) {
            throw $this->createNotFoundException();
        }

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

        return $this->render('timestamp/view.html.twig', [
            'form' => $form->createView(),
            'timestamp' => $timestamp,
            'tags' => ApiTag::fromEntities($timestamp->getTags()),
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
}

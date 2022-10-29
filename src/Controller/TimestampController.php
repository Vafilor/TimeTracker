<?php

declare(strict_types=1);

namespace App\Controller;

use App\Api\ApiTag;
use App\Entity\TagLink;
use App\Entity\Timestamp;
use App\Form\AddTimestampFormType;
use App\Form\EditTimestampFormType;
use App\Form\Model\AddTimestampModel;
use App\Form\Model\EditTimestampModel;
use App\Manager\TagManager;
use App\Manager\TimestampManager;
use App\Repository\StatisticValueRepository;
use App\Repository\TimestampRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

class TimestampController extends BaseController
{
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
            'direction' => 'desc',
        ]);

        $form = $this->createForm(AddTimestampFormType::class, new AddTimestampModel(), [
            'action' => $this->generateUrl('timestamp_create'),
        ]);

        return $this->renderForm('timestamp/index.html.twig', [
            'pagination' => $pagination,
            'form' => $form,
        ]);
    }

    #[Route('/timestamp/create', name: 'timestamp_create')]
    public function create(
        Request $request,
        EntityManagerInterface $manager,
        TagManager $tagManager,
        TimestampRepository $timestampRepository,
        PaginatorInterface $paginator): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $form = $this->createForm(AddTimestampFormType::class, new AddTimestampModel(), [
            'action' => $this->generateUrl('timestamp_create'),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AddTimestampModel $data */
            $data = $form->getData();
            $timestamp = new Timestamp($this->getUser());
            $manager->persist($timestamp);

            $tagNames = $tagManager->parseFromString($data->getTagIds());
            $tagObjects = $tagManager->findOrCreateByNames($tagNames, $this->getUser());
            foreach ($tagObjects as $tag) {
                $tagLink = new TagLink($timestamp, $tag);
                $timestamp->addTagLink($tagLink);
                $manager->persist($tagLink);
            }

            $manager->flush();

            return $this->redirectToRoute('timestamp_index');
        }

        $queryBuilder = $timestampRepository->findByUserQueryBuilder($this->getUser());
        $queryBuilder = $timestampRepository->preloadTags($queryBuilder);

        $pagination = $this->populatePaginationData($request, $paginator, $queryBuilder, [
            'sort' => 'timestamp.createdAt',
            'direction' => 'desc',
        ]);

        return $this->renderForm('timestamp/index.html.twig', [
            'pagination' => $pagination,
            'form' => $form,
        ]);
    }

    #[Route('/timestamp/{id}/repeat', name: 'timestamp_repeat', methods: ['POST'])]
    public function repeat(
        Request $request,
        TimestampManager $timestampManager,
        TimestampRepository $timestampRepository,
        string $id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $timestamp = $timestampRepository->findOrException($id);
        if (!$timestamp->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

//        Temporarily commented out as it is really annoying to open webpage from phone and have request fail
//        if (!$this->isCsrfTokenValid('repeat_timestamp', $request->request->get('_token'))) {
//            $this->addFlash('danger', 'Invalid CSRF token');
//            throw new BadRequestHttpException('Invalid CSRF token');
//        }

        $timestampManager->repeat($timestamp);
        $this->getDoctrine()->getManager()->flush();

        return $this->redirectToRoute('timestamp_index');
    }

    /**
     * @throws NonUniqueResultException
     */
    #[Route('/timestamp/{id}/view', name: 'timestamp_view')]
    public function view(
        Request $request,
        TimestampRepository $timestampRepository,
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

        if (!$timestamp->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(EditTimestampFormType::class, EditTimestampModel::fromEntity($timestamp), [
            'timezone' => $this->getUser()->getTimezone(),
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var EditTimestampModel $data */
            $data = $form->getData();

            $timestamp->setCreatedAt($data->getCreatedAt());
            $timestamp->setDescription($data->getDescription());

            $this->flush();

            $this->addFlash('success', 'Updated timestamp');

            return $this->redirectToRoute('timestamp_index');
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
        if (!$timestamp->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $manager = $this->getDoctrine()->getManager();
        $manager->remove($timestamp);
        $manager->flush();

        $this->addFlash('success', 'Timestamp was removed');

        return $this->redirectToRoute('timestamp_index');
    }

    #[Route('/timestamp/{id}/records', name: 'timestamp_record_index')]
    public function _recordIndex(
        Request $request,
        TimestampRepository $timestampRepository,
        StatisticValueRepository $statisticValueRepository,
        PaginatorInterface $paginator,
        string $id
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $timestamp = $timestampRepository->findOrException($id);
        if (!$timestamp->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $statisticValues = $statisticValueRepository->findForResource($timestamp);

        return $this->render('statistic_value/partials/_statistic-value-index.html.twig', [
            'values' => $statisticValues,
        ]);
    }
}

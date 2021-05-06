<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Timestamp;
use App\Form\Model\TimestampEditModel;
use App\Form\TimestampEditFormType;
use App\Repository\TaskRepository;
use App\Repository\TimeEntryRepository;
use App\Repository\TimestampRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TimestampController extends BaseController
{
    #[Route('/timestamp', name: 'timestamp_index')]
    public function index(
        Request $request,
        TimestampRepository $timestampRepository,
        FormFactoryInterface $formFactory,
        PaginatorInterface $paginator): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $queryBuilder = $timestampRepository->findByUserQueryBuilder($this->getUser());
        // TODO add filter

        $pagination = $this->populatePaginationData($request, $paginator, $queryBuilder, [
            'sort' => 'timestamp.createdAt',
            'direction' => 'desc'
        ]);

        return $this->render('timestamp/index.html.twig', [
            'pagination' => $pagination
        ]);
    }

    #[Route('/timestamp/create', name: 'timestamp_create')]
    public function create(Request $request) {
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
        string $id) {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        /** @var Timestamp|null $timestamp */
        $timestamp = $timestampRepository->find($id);

        if (!$this->getUser()->equalIds($timestamp->getCreatedBy())) {
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
            'timestamp' => $timestamp
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Controller;

use App\Api\ApiTag;
use App\Form\EditNoteFormType;
use App\Form\FilterNoteFormType;
use App\Form\Model\EditNoteModel;
use App\Form\Model\FilterNoteModel;
use App\Repository\NoteRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class NoteController extends BaseController
{
    #[Route('/note', name: 'note_index')]
    public function index(
        Request $request,
        NoteRepository $noteRepository,
        FormFactoryInterface $formFactory,
        PaginatorInterface $paginator
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $queryBuilder = $noteRepository->findByUserQueryBuilder($this->getUser());
        $queryBuilder = $noteRepository->preloadTags($queryBuilder);

        $filterForm = $formFactory->createNamed(
            '',
            FilterNoteFormType::class,
            new FilterNoteModel(),
            [
                'csrf_protection' => false,
                'method' => 'GET',
                'allow_extra_fields' => true
            ]
        );

        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            /** @var FilterNoteModel $data */
            $data = $filterForm->getData();

            $queryBuilder = $noteRepository->applyFilter($queryBuilder, $data);
        }

        $pagination = $this->populatePaginationData($request, $paginator, $queryBuilder, [
            'sort' => 'note.createdAt',
            'direction' => 'desc'
        ]);

        return $this->render('note/index.html.twig', [
            'pagination' => $pagination,
            'filterForm' => $filterForm->createView(),
        ]);
    }

    #[Route('/note/{id}/view', name: 'note_view')]
    public function view(
        Request $request,
        NoteRepository $noteRepository,
        string $id
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $note = $noteRepository->findOrException($id);
        if (!$note->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $noteModel = EditNoteModel::fromEntity($note);

        $form = $this->createForm(
            EditNoteFormType::class,
            $noteModel,
        );

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var EditNoteModel $data */
            $data = $form->getData();

            $note->setTitle($data->getTitle());
            $note->setContent($data->getContent());

            $this->getDoctrine()->getManager()->flush();

            $this->addFlash('success', 'Note successfully updated');
        }

        return $this->render(
            'note/view.html.twig',
            [
                'note' => $note,
                'form' => $form->createView(),
                'tags' => ApiTag::fromEntities($note->getTags()),
            ]
        );
    }

    #[Route('/note/{id}/delete', name: 'note_delete')]
    public function remove(
        Request $request,
        NoteRepository $noteRepository,
        string $id
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $note = $noteRepository->findOrException($id);
        if (!$note->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $this->doctrineRemove($note, true);

        $this->addFlash('success', 'Note successfully removed');

        return $this->redirectToRoute('note_index');
    }
}
<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Note;
use App\Form\AddNoteFormType;
use App\Form\EditNoteFormType;
use App\Form\FilterNoteFormType;
use App\Form\Model\AddNoteModel;
use App\Form\Model\EditNoteModel;
use App\Form\Model\FilterNoteModel;
use App\Repository\NoteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class NoteController extends BaseController
{
    private function createIndexFilterForm(FormFactoryInterface $formFactory): FormInterface
    {
        return $formFactory->createNamed(
            '',
            FilterNoteFormType::class,
            new FilterNoteModel(),
            [
                'csrf_protection' => false,
                'method' => 'GET',
                'allow_extra_fields' => true,
            ]
        );
    }

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

        $filterForm = $this->createIndexFilterForm($formFactory);

        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            /** @var FilterNoteModel $data */
            $data = $filterForm->getData();

            $queryBuilder = $noteRepository->applyFilter($queryBuilder, $data);
        }

        $pagination = $this->populatePaginationData($request, $paginator, $queryBuilder, [
            'sort' => 'note.createdAt',
            'direction' => 'desc',
        ]);

        $form = $this->createForm(AddNoteFormType::class, new AddNoteModel(), [
            'action' => $this->generateUrl('note_create'),
            'timezone' => $this->getUser()->getTimezone(),
        ]);

        return $this->renderForm('note/index.html.twig', [
            'pagination' => $pagination,
            'filterForm' => $filterForm,
            'form' => $form,
        ]);
    }

    #[Route('/note/create', name: 'note_create')]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        NoteRepository $noteRepository,
        PaginatorInterface $paginator,
        FormFactoryInterface $formFactory,
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $form = $this->createForm(AddNoteFormType::class, new AddNoteModel(), [
            'action' => $this->generateUrl('note_create'),
            'timezone' => $this->getUser()->getTimezone(),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AddNoteModel $data */
            $data = $form->getData();

            $newNote = new Note($this->getUser(), $data->getTitle(), $data->getContent());
            $newNote->setForDate($data->getForDate());

            $entityManager->getManager();
            $entityManager->persist($newNote);
            $entityManager->flush();

            return $this->redirectToRoute('note_index');
        }

        $queryBuilder = $noteRepository->findByUserQueryBuilder($this->getUser());
        $queryBuilder = $noteRepository->preloadTags($queryBuilder);

        $filterForm = $this->createIndexFilterForm($formFactory);
        $pagination = $this->populatePaginationData($request, $paginator, $queryBuilder, [
            'sort' => 'note.createdAt',
            'direction' => 'desc',
        ]);

        return $this->renderForm('note/index.html.twig', [
            'pagination' => $pagination,
            'filterForm' => $filterForm,
            'form' => $form,
        ]);
    }

    #[Route('/note/{id}/view', name: 'note_view')]
    public function view(
        Request $request,
        EntityManagerInterface $entityManager,
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
            ['timezone' => $this->getUser()->getTimezone()]
        );

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var EditNoteModel $data */
            $data = $form->getData();

            $note->setTitle($data->getTitle());
            if ($data->hasContent()) {
                $note->setContent($data->getContent());
            }
            $note->setForDate($data->getForDate());

            $entityManager->flush();

            $this->addFlash('success', 'Note successfully updated');
        }

        return $this->renderForm(
            'note/view.html.twig',
            [
                'note' => $note,
                'form' => $form,
            ]
        );
    }

    #[Route('/note/{id}/delete', name: 'note_delete')]
    public function remove(
        NoteRepository $noteRepository,
        EntityManagerInterface $entityManager,
        string $id
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $note = $noteRepository->findOrException($id);
        if (!$note->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $entityManager->remove($note);
        $entityManager->flush();

        $this->addFlash('success', 'Note successfully removed');

        return $this->redirectToRoute('note_index');
    }
}

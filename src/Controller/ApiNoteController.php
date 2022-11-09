<?php

declare(strict_types=1);

namespace App\Controller;

use App\Api\ApiFormError;
use App\Api\ApiNote;
use App\Api\ApiProblem;
use App\Api\ApiProblemException;
use App\Entity\Note;
use App\Form\AddNoteFormType;
use App\Form\EditNoteFormType;
use App\Form\Model\AddNoteModel;
use App\Form\Model\EditNoteModel;
use App\Manager\TagManager;
use App\Repository\NoteRepository;
use App\Repository\TagLinkRepository;
use App\Repository\TagRepository;
use App\Traits\TaggableController;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApiNoteController extends BaseController
{
    use TaggableController;

    #[Route('/api/note', name: 'api_note_create', methods: ['POST'])]
    #[Route('/json/note', name: 'json_note_create', methods: ['POST'])]
    public function create(
        Request $request
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $form = $this->createForm(
            AddNoteFormType::class,
            new AddNoteModel(),
            [
                'csrf_protection' => false,
                'timezone' => $this->getUser()->getTimezone(),
            ]
        );

        $data = $this->getJsonBody($request);

        try {
            $form->submit($data);
        } catch (InvalidArgumentException) {
            throw new ApiProblemException(new ApiProblem(Response::HTTP_BAD_REQUEST, ApiProblem::TYPE_VALIDATION_ERROR));
        }

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AddNoteModel $data */
            $data = $form->getData();

            $newNote = new Note($this->getUser(), $data->getTitle(), $data->getContent());

            $manager = $this->getDoctrine()->getManager();
            $manager->persist($newNote);
            $manager->flush();

            $apiNote = ApiNote::fromEntity($newNote, $this->getUser());
            if (str_starts_with($request->getPathInfo(), '/json')) {
                $apiNote->url = $this->generateUrl('note_view', ['id' => $newNote->getIdString()]);
            } else {
                $apiNote->url = $this->generateUrl('api_note_view', ['id' => $newNote->getIdString()]);
            }

            return $this->jsonNoNulls($apiNote);
        } elseif (!$form->isValid()) {
            $formError = new ApiFormError($form->getErrors(true));
            throw new ApiProblemException($formError);
        }

        $error = new ApiProblem(Response::HTTP_BAD_REQUEST, ApiProblem::TYPE_INVALID_ACTION);
        throw new ApiProblemException($error);
    }

    #[Route('/api/note/{id}', name: 'api_note_view', methods: ['GET'])]
    public function view(
        NoteRepository $noteRepository,
        string $id
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $note = $noteRepository->findOrException($id);
        if (!$note->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $apiNote = ApiNote::fromEntity($note, $this->getUser());

        return $this->jsonNoNulls($apiNote);
    }

    #[Route('/api/note/{id}', name: 'api_note_edit', methods: ['PUT'])]
    #[Route('/json/note/{id}', name: 'json_note_update', methods: ['PUT'])]
    public function edit(
        Request $request,
        NoteRepository $noteRepository,
        string $id
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $note = $noteRepository->findOrException($id);
        if (!$note->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(EditNoteFormType::class, EditNoteModel::fromEntity($note), [
            'csrf_protection' => false,
            'timezone' => $this->getUser()->getTimezone(),
        ]);

        $data = $this->getJsonBody($request);

        try {
            $form->submit($data);
        } catch (InvalidArgumentException) {
            throw new ApiProblemException(new ApiProblem(Response::HTTP_BAD_REQUEST, ApiProblem::TYPE_VALIDATION_ERROR));
        }

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var EditNoteModel $data */
            $data = $form->getData();
            if ($data->hasTitle()) {
                $note->setTitle($data->getTitle());
            }

            if ($data->hasContent()) {
                $note->setContent($data->getContent());
            }

            $this->getDoctrine()->getManager()->flush();
        } elseif (!$form->isValid()) {
            $formError = new ApiFormError($form->getErrors(true));
            throw new ApiProblemException($formError);
        }

        $apiNote = ApiNote::fromEntity($note, $this->getUser());

        return $this->jsonNoNulls($apiNote);
    }

    #[Route('/api/note/{id}/tag', name: 'api_note_tag_create', methods: ['POST'])]
    #[Route('/json/note/{id}/tag', name: 'json_note_tag_create', methods: ['POST'])]
    public function addTag(
        Request $request,
        NoteRepository $noteRepository,
        TagManager $tagManager,
        TagLinkRepository $tagLinkRepository,
        string $id
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $note = $noteRepository->findOrException($id);
        if (!$note->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        return $this->addTagRequest(
            $request,
            $tagManager,
            $tagLinkRepository,
            $this->getUser(),
            $note
        );
    }

    #[Route('/api/note/{id}/tag/{tagName}', name: 'api_note_tag_delete', methods: ['DELETE'])]
    #[Route('/json/note/{id}/tag/{tagName}', name: 'json_note_tag_delete', methods: ['DELETE'])]
    public function deleteTag(
        NoteRepository $noteRepository,
        TagRepository $tagRepository,
        TagLinkRepository $tagLinkRepository,
        string $id,
        string $tagName
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $note = $noteRepository->findOrException($id);
        if (!$note->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        return $this->removeTagRequest(
            $tagRepository,
            $tagLinkRepository,
            $this->getUser(),
            $tagName,
            $note
        );
    }

    #[Route('/api/note/{id}/tags', name: 'api_note_tags', methods: ['GET'])]
    #[Route('/json/note/{id}/tags', name: 'json_note_tags', methods: ['GET'])]
    public function indexTag(
        NoteRepository $noteRepository,
        string $id
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $note = $noteRepository->findOrException($id);
        if (!$note->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        return $this->getTagsRequest($note);
    }
}

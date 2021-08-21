<?php

declare(strict_types=1);

namespace App\Traits;

use App\Api\ApiFormError;
use App\Api\ApiProblem;
use App\Api\ApiProblemException;
use App\Api\ApiTag;
use App\Controller\TagController;
use App\Entity\Note;
use App\Entity\Statistic;
use App\Entity\TagLink;
use App\Entity\Task;
use App\Entity\TimeEntry;
use App\Entity\Timestamp;
use App\Entity\User;
use App\Form\AddTagFormType;
use App\Form\Model\AddTagModel;
use App\Manager\TagManager;
use App\Repository\TagLinkRepository;
use App\Repository\TagRepository;
use App\Util\TypeUtil;
use InvalidArgumentException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

trait TaggableController
{
    abstract protected function createForm(string $type, $data = null, array $options = []): FormInterface;
    abstract protected function json($data, int $status = 200, array $headers = [], array $context = []): JsonResponse;
    abstract protected function createNotFoundException(string $message = 'Not Found', Throwable $previous = null): NotFoundHttpException;
    abstract public function getJsonBody(Request $request, array $default = null): array;
    abstract public function persist(mixed $obj, bool $flush = false): void;
    abstract public function doctrineRemove(mixed $obj, bool $flush = false): void;
    abstract public function jsonNoNulls($data, int $status = 200, array $headers = [], array $context = []): JsonResponse;
    abstract public function jsonNoContent(): JsonResponse;

    public function addTagRequest(
        Request $request,
        TagManager $tagManager,
        TagLinkRepository $tagLinkRepository,
        User $assignedTo,
        TimeEntry|Timestamp|Task|Statistic|Note $resource
    ): JsonResponse {
        $form = $this->createForm(AddTagFormType::class, new AddTagModel(), [
            'csrf_protection' => false,
        ]);

        $data = $this->getJsonBody($request);

        try {
            $form->submit($data);
        } catch (InvalidArgumentException $e) {
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

        /** @var AddTagModel $data */
        $data = $form->getData();

        $tag = $tagManager->findOrCreateByName($data->getName(), $assignedTo);
        $existingLink = $tagLinkRepository->findForResource($resource, $tag);
        if (!is_null($existingLink)) {
            return $this->json([], Response::HTTP_CONFLICT);
        }

        $tagLink = new TagLink($resource, $tag);

        $this->persist($tagLink, true);

        $apiTag = ApiTag::fromEntity($tag);

        return $this->jsonNoNulls($apiTag, Response::HTTP_CREATED);
    }

    public function removeTagRequest(
        TagRepository $tagRepository,
        TagLinkRepository $tagLinkRepository,
        User $assignedTo,
        string $tagName,
        TimeEntry|Timestamp|Task|Statistic|Note $resource
    ): JsonResponse {
        $tag = $tagRepository->findWithUserName($assignedTo, $tagName);
        if (is_null($tag)) {
            throw $this->createNotFoundException();
        }

        $existingLink = $tagLinkRepository->findForResource($resource, $tag);
        if (is_null($existingLink)) {
            $className = TypeUtil::getClassName($resource);

            throw new ApiProblemException(
                ApiProblem::invalidAction(
                    TagController::CODE_TAG_NOT_ASSOCIATED,
                    "Tag '$tagName' is not associated to this $className"
                )
            );
        }

        $this->doctrineRemove($existingLink, true);

        return $this->jsonNoContent();
    }

    public function getTagsRequest(TaggableTrait $taggableTrait): JsonResponse
    {
        $apiTags = ApiTag::fromEntities($taggableTrait->getTags());

        return $this->jsonNoNulls($apiTags);
    }
}

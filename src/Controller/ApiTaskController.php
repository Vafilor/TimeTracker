<?php

declare(strict_types=1);

namespace App\Controller;

use App\Api\ApiError;
use App\Api\ApiFormError;
use App\Api\ApiPagination;
use App\Api\ApiProblem;
use App\Api\ApiProblemException;
use App\Api\ApiTag;
use App\Api\ApiTask;
use App\Entity\Tag;
use App\Entity\TagLink;
use App\Entity\Task;
use App\Form\Model\TaskListFilterModel;
use App\Form\Model\TaskModel;
use App\Form\TaskFormType;
use App\Form\TaskListFilterFormType;
use App\Manager\TagManager;
use App\Repository\TagLinkRepository;
use App\Repository\TagRepository;
use App\Repository\TaskRepository;
use App\Traits\TaggableController;
use InvalidArgumentException;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApiTaskController extends BaseController
{
    use TaggableController;

    #[Route('/api/task', name: 'api_task_index', methods: ["GET"])]
    #[Route('/json/task', name: 'json_task_index', methods: ["GET"])]
    public function index(
        Request $request,
        TaskRepository $taskRepository,
        FormFactoryInterface $formFactory,
        PaginatorInterface $paginator
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $queryBuilder = $taskRepository->findByUserQueryBuilder($this->getUser());

        $filterForm = $formFactory->createNamed(
            '',
            TaskListFilterFormType::class,
            new TaskListFilterModel(),
            [
                'csrf_protection' => false,
                'method' => 'GET',
                'allow_extra_fields' => true
            ]
        );

        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            /** @var TaskListFilterModel $data */
            $data = $filterForm->getData();

            $taskRepository->applyFilter($queryBuilder, $data);
        } else {
            $queryBuilder = $taskRepository->applyNotCompleted($queryBuilder);
        }

        $pagination = $this->populatePaginationData(
            $request,
            $paginator,
            $queryBuilder,
            [
                'sort' => 'task.createdAt',
                'direction' => 'desc'
            ]
        );

        $items = ApiTask::fromEntities($pagination->getItems(), $this->getUser());

        return $this->jsonNoNulls(ApiPagination::fromPagination($pagination, $items));
    }

    #[Route('/api/task', name: 'api_task_create', methods: ["POST"])]
    #[Route('/json/task', name: 'task_json_create', methods: ["POST"])]
    public function create(
        Request $request
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $form = $this->createForm(
            TaskFormType::class,
            new TaskModel(),
            [
                'timezone' => $this->getUser()->getTimezone(),
                'csrf_protection' => false
            ],
        );

        $data = $this->getJsonBody($request);

        try {
            $form->submit($data);
        } catch (InvalidArgumentException $invalidArgumentException) {
            throw new ApiProblemException(new ApiProblem(Response::HTTP_BAD_REQUEST, ApiProblem::TYPE_VALIDATION_ERROR));
        }

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var TaskModel $data */
            $data = $form->getData();

            $newTask = new Task($this->getUser(), $data->getName());
            $newTask->setDescription($data->getDescription());

            $manager = $this->getDoctrine()->getManager();
            $manager->persist($newTask);
            $manager->flush();

            $apiTimeEntry = ApiTask::fromEntity($newTask, $this->getUser());

            return $this->jsonNoNulls($apiTimeEntry);
        } elseif (!$form->isValid()) {
            $formError = new ApiFormError($form->getErrors(true));
            throw new ApiProblemException($formError);
        }


        $error = new ApiProblem(Response::HTTP_BAD_REQUEST, ApiProblem::TYPE_INVALID_ACTION);
        throw new ApiProblemException($error);
    }

    #[Route('/api/task/{id}', name: 'api_task_view', methods: ["GET"])]
    public function view(
        Request $request,
        TaskRepository $taskRepository,
        string $id
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $task = $taskRepository->findOrException($id);
        if (!$task->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $apiTask = ApiTask::fromEntity($task, $this->getUser());

        return $this->jsonNoNulls($apiTask);
    }

    /**
     * Change the completedAt status of a Task.
     * If the body has a json field of "checked", as in
     * {
     *  "checked": true|false
     * }
     *
     * then that value is used. Otherwise, it defaults to "true".
     */
    #[Route('/api/task/{id}/check', name: 'api_task_complete', methods: ['PUT'])]
    #[Route('/json/task/{id}/check', name: 'json_task_complete', methods: ['PUT'])]
    public function jsonComplete(
        Request $request,
        TaskRepository $taskRepository,
        string $id
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $task = $taskRepository->findOrException($id);
        if (!$task->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $completed = true;
        $data = $this->getJsonBody($request);
        if (array_key_exists('completed', $data)) {
            $completed = $data['completed'];
        }

        if ($completed) {
            $task->complete();
        } else {
            $task->clearCompleted();
        }

        $this->getDoctrine()->getManager()->flush();

        $apiTask = ApiTask::fromEntity($task, $this->getUser());

        return $this->jsonNoNulls($apiTask);
    }

    #[Route('/json/task/{id}', name: 'task_json_update', methods: ['PUT'])]
    public function jsonUpdate(Request $request, TaskRepository $taskRepository, string $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $task = $taskRepository->findOrException($id);
        if (!$task->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $data = $this->getJsonBody($request);

        if (array_key_exists('description', $data)) {
            $task->setDescription($data['description']);
        }

        $this->getDoctrine()->getManager()->flush();

        $apiTask = ApiTask::fromEntity($task, $this->getUser());

        return $this->jsonNoNulls($apiTask);
    }

    #[Route('/api/task/{id}/tag', name: 'api_task_tag_create', methods: ['POST'])]
    #[Route('/json/task/{id}/tag', name: 'json_task_tag_create', methods: ['POST'])]
    public function addTag(
        Request $request,
        TaskRepository $taskRepository,
        TagManager $tagManager,
        TagLinkRepository $tagLinkRepository,
        string $id
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $task = $taskRepository->findOrException($id);
        if (!$task->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        return $this->addTagRequest(
            $request,
            $tagManager,
            $tagLinkRepository,
            $this->getUser(),
            $task
        );
    }

    #[Route('/api/task/{id}/tag/{tagName}', name: 'api_task_tag_delete', methods: ['DELETE'])]
    #[Route('/json/task/{id}/tag/{tagName}', name: 'json_task_tag_delete', methods: ['DELETE'])]
    public function deleteTag(
        Request $request,
        TaskRepository $taskRepository,
        TagRepository $tagRepository,
        TagLinkRepository $tagLinkRepository,
        string $id,
        string $tagName
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $task = $taskRepository->findOrException($id);
        if (!$task->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        return $this->removeTagRequest(
            $tagRepository,
            $tagLinkRepository,
            $this->getUser(),
            $tagName,
            $task
        );
    }

    #[Route('/api/task/{id}/tags', name: 'api_task_tags', methods: ["GET"])]
    #[Route('/json/task/{id}/tags', name: 'json_task_tags', methods: ["GET"])]
    public function indexTag(
        Request $request,
        TaskRepository $taskRepository,
        string $id
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $task = $taskRepository->findOrException($id);
        if (!$task->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        return $this->getTagsRequest($task);
    }
}

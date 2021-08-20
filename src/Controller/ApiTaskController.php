<?php

declare(strict_types=1);

namespace App\Controller;

use App\Api\ApiError;
use App\Api\ApiFormError;
use App\Api\ApiPagination;
use App\Api\ApiProblem;
use App\Api\ApiProblemException;
use App\Api\ApiTask;
use App\Entity\Task;
use App\Form\AddTaskFormType;
use App\Form\Model\AddTaskModel;
use App\Form\Model\FilterTaskModel;
use App\Form\Model\EditTaskModel;
use App\Form\EditTaskFormType;
use App\Form\FilterTaskFormType;
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
            FilterTaskFormType::class,
            new FilterTaskModel(),
            [
                'csrf_protection' => false,
                'method' => 'GET',
            ]
        );

        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            /** @var FilterTaskModel $data */
            $data = $filterForm->getData();

            $taskRepository->applyFilter($queryBuilder, $data);
        } elseif ($filterForm->isSubmitted() && !$filterForm->isValid()) {
            $formError = new ApiFormError($filterForm->getErrors(true));
            throw new ApiProblemException($formError);
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
    #[Route('/json/task', name: 'json_task_create', methods: ["POST"])]
    public function create(
        Request $request,
        TaskRepository $taskRepository
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $form = $this->createForm(
            AddTaskFormType::class,
            new AddTaskModel(),
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

        if (!$form->isSubmitted()) {
            throw new ApiProblemException(
                ApiFormError::invalidAction('bad_data', 'Form not submitted')
            );
        }

        if (!$form->isValid()) {
            $formError = new ApiFormError($form->getErrors(true));
            throw new ApiProblemException($formError);
        }

        /** @var EditTaskModel $data */
        $data = $form->getData();

        $newTask = new Task($this->getUser(), $data->getName());
        $newTask->setDescription($data->getDescription());
        if ($data->hasParentTask()) {
            $parentTask = $taskRepository->findOrException($data->getParentTask());
            $newTask->setParent($parentTask);
        }

        $manager = $this->getDoctrine()->getManager();
        $manager->persist($newTask);
        $manager->flush();

        $apiTask = ApiTask::fromEntity($newTask, $this->getUser());

        if (str_starts_with($request->getPathInfo(), '/json')) {
            $response = [
                'task' => $apiTask,
                'view' => $this->renderView('task/partials/_task.html.twig', ['task' => $newTask])
            ];

            return $this->jsonNoNulls($response, Response::HTTP_CREATED);
        }

        return $this->jsonNoNulls($apiTask, Response::HTTP_CREATED);
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

    #[Route('/json/task/{id}/lineage', name: 'json_task_lineage', methods: ['GET'])]
    #[Route('/api/task/{id}/lineage', name: 'api_task_lineage', methods: ['GET'])]
    public function getLineage(Request $request, TaskRepository $taskRepository, string $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $task = $taskRepository->findOrException($id);
        if (!$task->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $lineage = $task->getLineage();

        $apiLineage = array_map(
            fn (Task $task) => ApiTask::fromEntity($task, $this->getUser()),
            $lineage
        );

        return $this->jsonNoNulls($apiLineage);
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
    public function complete(
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

    #[Route('/json/task/{id}', name: 'json_task_update', methods: ['PUT'])]
    #[Route('/api/task/{id}', name: 'api_task_update', methods: ['PUT'])]
    public function update(Request $request, TaskRepository $taskRepository, string $id): JsonResponse
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
        if (array_key_exists('parentTaskId', $data)) {
            $parent = $taskRepository->findOrException($data['parentTaskId']);
            $task->setParent($parent);
        }

        $this->getDoctrine()->getManager()->flush();

        $apiTask = ApiTask::fromEntity($task, $this->getUser());

        return $this->jsonNoNulls($apiTask);
    }

    #[Route('/json/task/{id}/parent', name: 'json_task_parent_update', methods: ['PUT'])]
    #[Route('/api/task/{id}/parent', name: 'api_task_parent_update', methods: ['PUT'])]
    public function updateParentTask(Request $request, TaskRepository $taskRepository, string $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $task = $taskRepository->findOrException($id);
        if (!$task->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        if ($task->isDeleted()) {
            $this->createNotFoundException();
        }

        $data = $this->getJsonBody($request);

        if (!array_key_exists('parentTaskId', $data)) {
            $problem = ApiProblem::withErrors(
                Response::HTTP_BAD_REQUEST,
                ApiProblem::TYPE_INVALID_REQUEST_BODY,
                ApiError::missingProperty('parentTaskId')
            );

            throw new ApiProblemException($problem);
        }

        $parent = $taskRepository->findOrException($data['parentTaskId']);
        $task->setParent($parent);

        $this->getDoctrine()->getManager()->flush();

        $apiTask = ApiTask::fromEntity($parent, $this->getUser());

        if (str_starts_with($request->getPathInfo(), '/json')) {
            $apiTask->url = $this->generateUrl('task_view', ['id' => $parent->getIdString()]);
        }

        return $this->jsonNoNulls($apiTask);
    }

    #[Route('/json/task/{id}/parent', name: 'json_task_parent_delete', methods: ['DELETE'])]
    #[Route('/api/task/{id}/parent', name: 'api_task_parent_delete', methods: ['DELETE'])]
    public function removeParentTask(Request $request, TaskRepository $taskRepository, string $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $task = $taskRepository->findOrException($id);
        if (!$task->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        if (!$task->hasParent()) {
            throw new ApiProblemException(
                ApiProblem::invalidAction(
                    TaskController::CODE_NO_PARENT_TASK,
                    'Task has no parent task',
                )
            );
        }

        $task->removeParent();
        $this->getDoctrine()->getManager()->flush();

        return $this->jsonNoContent();
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

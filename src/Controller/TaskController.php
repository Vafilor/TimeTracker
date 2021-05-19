<?php

declare(strict_types=1);

namespace App\Controller;

use App\Api\ApiPagination;
use App\Api\ApiTask;
use App\Entity\Task;
use App\Form\Model\TaskListFilterModel;
use App\Form\Model\TaskModel;
use App\Form\TaskFormType;
use App\Form\TaskListFilterFormType;
use App\Repository\TaskRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TaskController extends BaseController
{
    private TaskRepository $taskRepository;

    public function __construct(TaskRepository $taskRepository)
    {
        $this->taskRepository = $taskRepository;
    }

    #[Route('/task', name: 'task_index')]
    public function index(
        Request $request,
        FormFactoryInterface $formFactory,
        PaginatorInterface $paginator
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $queryBuilder = $this->taskRepository->findByUserQueryBuilder($this->getUser());

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

            $this->taskRepository->applyFilter($queryBuilder, $data);
        } else {
            $queryBuilder = $this->taskRepository->applyNotCompleted($queryBuilder);
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

        return $this->render(
            'task/index.html.twig',
            [
                'pagination' => $pagination,
                'filterForm' => $filterForm->createView()
            ]
        );
    }

    #[Route('/json/task', name: 'task_json_list', methods: ["GET"])]
    public function jsonList(
        Request $request,
        PaginatorInterface $paginator
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $queryBuilder = $this->taskRepository->findByUserQueryBuilder($this->getUser());

        $name = $request->query->get('name');
        if (!is_null($name)) {
            $name = strtolower($name);
            // TODO separate column that's lowercase for name
            $queryBuilder = $queryBuilder->andWhere('LOWER(task.name) LIKE :name')
                                         ->setParameter('name', "%$name%")
            ;
        }

        $pagination = $this->populatePaginationData($request, $paginator, $queryBuilder, [
            'sort' => 'task.createdAt',
            'direction' => 'desc'
        ]);

        $items = ApiTask::fromEntities($pagination->getItems(), $this->getUser());

        return $this->json(ApiPagination::fromPagination($pagination, $items));
    }

    #[Route('/task/create', name: 'task_create')]
    public function create(
        Request $request
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $form = $this->createForm(
            TaskFormType::class,
            new TaskModel(),
            [
                'timezone' => $this->getUser()->getTimezone()
            ]
        );
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var TaskModel $data */
            $data = $form->getData();

            $newTask = new Task($this->getUser(), $data->getName());
            $newTask->setDescription($data->getDescription());

            $manager = $this->getDoctrine()->getManager();
            $manager->persist($newTask);
            $manager->flush();

            $this->addFlash('success', 'Task successfully created');

            return $this->redirectToRoute('task_view', ['id' => $newTask->getIdString()]);
        }

        return $this->render(
            'task/create.html.twig',
            [
                'form' => $form->createView()
            ]
        );
    }

    #[Route('/task/{id}/view', name: 'task_view')]
    public function view(
        Request $request,
        string $id
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $task = $this->taskRepository->findOrException($id);
        if (!$task->wasCreatedBy($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $taskModel = TaskModel::fromEntity($task);

        $form = $this->createForm(
            TaskFormType::class,
            $taskModel,
            [
                'timezone' => $this->getUser()->getTimezone()
            ]
        );
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var TaskModel $data */
            $data = $form->getData();

            $task->setName($data->getName());
            $task->setDescription($data->getDescription());
            $task->setCompletedAt($data->getCompletedAt());

            $this->getDoctrine()->getManager()->flush();

            $this->addFlash('success', 'Task successfully updated');
        }

        return $this->render(
            'task/view.html.twig',
            [
                'task' => $task,
                'form' => $form->createView()
            ]
        );
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
    #[Route('/json/task/{id}/check', name: 'task_json_complete', methods: ['PUT'])]
    public function jsonComplete(
        Request $request,
        string $id
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $task = $this->taskRepository->findOrException($id);
        if (!$task->wasCreatedBy($this->getUser())) {
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
            $task->setCompletedAt(null);
        }

        $this->getDoctrine()->getManager()->flush();

        $apiTask = ApiTask::fromEntity($task, $this->getUser());

        return $this->json($apiTask);
    }

    #[Route('/json/task/{id}', name: 'task_json_update', methods: ['PUT'])]
    public function jsonUpdate(Request $request, string $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $task = $this->taskRepository->findOrException($id);
        if (!$task->wasCreatedBy($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $data = $this->getJsonBody($request);

        if (array_key_exists('description', $data)) {
            $task->setDescription($data['description']);
        }

        $this->getDoctrine()->getManager()->flush();

        $apiTask = ApiTask::fromEntity($task, $this->getUser());

        return $this->json($apiTask);
    }
}

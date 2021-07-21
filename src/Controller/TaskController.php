<?php

declare(strict_types=1);

namespace App\Controller;

use App\Api\ApiTag;
use App\Api\ApiTask;
use App\Entity\Task;
use App\Form\Model\TaskListFilterModel;
use App\Form\Model\TaskModel;
use App\Form\TaskFormType;
use App\Form\TaskListFilterFormType;
use App\Repository\TaskRepository;
use DateTime;
use DateTimeZone;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TaskController extends BaseController
{
    const CODE_NO_PARENT_TASK = 'code_no_parent_task';

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
        $queryBuilder = $this->taskRepository->preloadTags($queryBuilder);

        $filterForm = $formFactory->createNamed(
            '',
            TaskListFilterFormType::class,
            new TaskListFilterModel(),
            [
                'csrf_protection' => false,
                'method' => 'GET',
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

    #[Route('/task/create', name: 'task_create')]
    public function create(
        Request $request
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $form = $this->createForm(
            TaskFormType::class,
            new TaskModel(),
            ['timezone' => $this->getUser()->getTimezone()]
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
        if (!$task->isAssignedTo($this->getUser())) {
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

        $apiTags = ApiTag::fromEntities($task->getTags());

        return $this->render(
            'task/view.html.twig',
            [
                'task' => $task,
                'form' => $form->createView(),
                'tags' => $apiTags,
                'subtasks' => $task->getSubtasks()
            ]
        );
    }

    #[Route('/task/{id}/lineage', name: 'task_lineage', methods: ['GET'])]
    public function getLineage(Request $request, TaskRepository $taskRepository, string $id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $task = $taskRepository->findOrException($id);
        if (!$task->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $lineage = $task->getLineage();

        return $this->render('task/partials/_breadcrumbs.html.twig', [
            'tasks' => $lineage
        ]);
    }

    #[Route('/task/{id}/delete', name: 'task_delete')]
    public function remove(Request $request, TaskRepository $taskRepository, string $id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $task = $taskRepository->findOrException($id);
        if (!$task->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $now = new DateTime('now');

        $task->softDelete($now);
        $parentIds = [$task->getIdString()];
        while (count($parentIds) !== 0) {
            $subtasks = $taskRepository->findByKeys('parent', $parentIds);
            $parentIds = [];

            foreach ($subtasks as $subtask) {
                $parentIds[] = $subtask->getIdString();
                $subtask->softDelete($now);
            }
        }

        $this->flush();

        $this->addFlash('success', "Task '{$task->getName()}' and it's children have been deleted");

        return $this->redirectToRoute('task_index');
    }
}

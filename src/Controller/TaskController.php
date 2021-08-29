<?php

declare(strict_types=1);

namespace App\Controller;

use App\Api\ApiTag;
use App\Api\ApiTask;
use App\Entity\Task;
use App\Form\AddTaskFormType;
use App\Form\ExampleFormType;
use App\Form\Model\AddTaskModel;
use App\Form\Model\FilterTaskModel;
use App\Form\Model\EditTaskModel;
use App\Form\EditTaskFormType;
use App\Form\FilterTaskFormType;
use App\Repository\TaskRepository;
use DateTime;
use DateTimeZone;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;

class TaskController extends BaseController
{
    const CODE_NO_PARENT_TASK = 'code_no_parent_task';
    const CODE_NO_ASSIGNED_TASK = 'code_no_assigned_task';

    private TaskRepository $taskRepository;

    public function __construct(TaskRepository $taskRepository)
    {
        $this->taskRepository = $taskRepository;
    }

    private function createIndexFilterForm(FormFactoryInterface $formFactory): FormInterface {
        return $formFactory->createNamed(
            '',
            FilterTaskFormType::class,
            new FilterTaskModel(),
            [
                'method' => 'GET',
                'csrf_protection' => false,
                'allow_extra_fields' => true,
            ]
        );
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

        $filterForm = $this->createIndexFilterForm($formFactory);
        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            /** @var FilterTaskModel $data */
            $data = $filterForm->getData();

            $this->taskRepository->applyFilter($queryBuilder, $data);
        } else {
            $queryBuilder = $this->taskRepository->applyNotCompleted($queryBuilder);
            $queryBuilder = $this->taskRepository->applyNoSubtasks($queryBuilder);
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

        $form = $this->createForm(
            AddTaskFormType::class,
            new AddTaskModel(),
            [
                'timezone' => $this->getUser()->getTimezone(),
                'action' => $this->generateUrl('task_create')
            ],
        );

        return $this->renderForm('task/index.html.twig', [
                'pagination' => $pagination,
                'filterForm' => $filterForm,
                'form' => $form
            ]
        );
    }

    #[Route('/task_partial', name: 'task_index_partial', methods: ["GET"])]
    public function partialIndex(
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
            throw new HttpException(Response::HTTP_BAD_REQUEST, 'Invalid query parameters');
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

        return $this->render('task/partials/_task_list.html.twig', [
            'pagination' => $pagination
        ]);
    }

    #[Route('/task/create', name: 'task_create')]
    public function create(
        Request $request,
        TaskRepository $taskRepository,
        PaginatorInterface $paginator,
        FormFactoryInterface $formFactory
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $form = $this->createForm(
            AddTaskFormType::class,
            new AddTaskModel(),
            [
                'timezone' => $this->getUser()->getTimezone(),
                'action' => $this->generateUrl('task_create')
            ]
        );

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AddTaskModel $data */
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

            return $this->redirectToRoute('task_index');
        }

        $queryBuilder = $this->taskRepository->findByUserQueryBuilder($this->getUser());
        $queryBuilder = $this->taskRepository->preloadTags($queryBuilder);
        $pagination = $this->populatePaginationData(
            $request,
            $paginator,
            $queryBuilder,
            [
                'sort' => 'task.createdAt',
                'direction' => 'desc'
            ]
        );

        $filterForm = $this->createIndexFilterForm($formFactory);

        return $this->renderForm('task/index.html.twig', [
           'pagination' => $pagination,
           'filterForm' => $filterForm,
           'form' => $form
       ]);
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

        $taskModel = EditTaskModel::fromEntity($task);

        $form = $this->createForm(
            EditTaskFormType::class,
            $taskModel,
            ['timezone' => $this->getUser()->getTimezone()]
        );
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var EditTaskModel $data */
            $data = $form->getData();

            $task->setName($data->getName());
            $task->setDescription($data->getDescription());
            $task->setCompletedAt($data->getCompletedAt());
            $task->setDueAt($data->getDueAt());

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

    #[Route('/task/{id}/complete', name: 'task_complete')]
    public function complete(Request $request, TaskRepository $taskRepository, string $id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $task = $taskRepository->findOrException($id);
        if (!$task->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $completed = $request->query->get('value', 'complete') === 'complete';
        if ($completed && !$task->completed()) {
            $task->complete();
        } elseif (!$completed && $task->completed()) {
            $task->clearCompleted();
        }

        $this->flush();

        return $this->redirectToRoute('task_view', ['id' => $task->getIdString()]);
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

    #[Route('/example', name: 'example', methods: ["GET"])]
    public function example(
        Request $request,
        FormFactoryInterface $formFactory,
        PaginatorInterface $paginator
    ): Response {

        $form = $formFactory->createNamed(
            '',
            ExampleFormType::class,
            new FilterTaskModel(),
            [
                'method' => 'GET',
                'csrf_protection' => false,
                'allow_extra_fields' => true,
            ]
        );

        $form->handleRequest($request);

        return $this->renderForm('example/index.html.twig', [
            'form' => $form
        ]);
    }
}

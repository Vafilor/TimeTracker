<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\TagLink;
use App\Entity\Task;
use App\Form\ActionTaskFormType;
use App\Form\AddTaskFormType;
use App\Form\EditTaskFormType;
use App\Form\EditTaskPartialFormType;
use App\Form\FilterTaskFormType;
use App\Form\Model\ActionTaskModel;
use App\Form\Model\AddTaskModel;
use App\Form\Model\EditTaskModel;
use App\Form\Model\EditTaskPartialModel;
use App\Form\Model\FilterTaskModel;
use App\Manager\TagManager;
use App\Manager\TaskManager;
use App\Repository\TaskRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use LogicException;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;

class TaskController extends BaseController
{
    public const CODE_NO_PARENT_TASK = 'code_no_parent_task';
    public const CODE_NO_ASSIGNED_TASK = 'code_no_assigned_task';

    public function __construct(private TaskRepository $taskRepository)
    {
    }

    private function createIndexFilterForm(FormFactoryInterface $formFactory): FormInterface
    {
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
            $queryBuilder = $this->taskRepository->applyNotClosed($queryBuilder);
            $queryBuilder = $this->taskRepository->applyNoSubtasks($queryBuilder);
        }

        if ('task.completedAt' === $request->query->get('sort', '')) {
            $queryBuilder = $this->taskRepository->applyCompleted($queryBuilder);
        }

        if ('task.timeEstimate' === $request->query->get('sort', '')) {
            $direction = $request->query->get('direction');

            // We add a fake column h_timeEstimate since timeEstimate values can be null
            // The logic is in orderByTimeEstimate
            $request->query->set('sort', 'h_timeEstimate');
            $queryBuilder = $this->taskRepository->orderByTimeEstimate($queryBuilder, $direction);
        }

        $pagination = $this->populatePaginationData(
            $request,
            $paginator,
            $queryBuilder,
            [
                'sort' => 'task.createdAt',
                'direction' => 'desc',
            ]
        );

        $form = $this->createForm(
            AddTaskFormType::class,
            new AddTaskModel(),
            [
                'timezone' => $this->getUser()->getTimezone(),
                'action' => $this->generateUrl('task_create'),
            ],
        );

        return $this->render(
            'task/index.html.twig',
            [
                'pagination' => $pagination,
                'filterForm' => $filterForm,
                'form' => $form,
            ]
        );
    }

    #[Route('/task_partial', name: 'task_index_partial', methods: ['GET'])]
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
                'direction' => 'desc',
            ]
        );

        return $this->render('task/partials/_task_list.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/task/create', name: 'task_create')]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        TaskRepository $taskRepository,
        TaskManager $taskManager,
        PaginatorInterface $paginator,
        FormFactoryInterface $formFactory
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $form = $this->createForm(
            AddTaskFormType::class,
            new AddTaskModel(),
            [
                'timezone' => $this->getUser()->getTimezone(),
                'action' => $this->generateUrl('task_create'),
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

            if ($data->hasTaskTemplate()) {
                $taskTemplate = $taskRepository->findOrException($data->getTaskTemplate());
                if (!$taskTemplate->isTemplate()) {
                    $this->addFlash('danger', 'Task template is not set as a template');

                    return $this->redirectToRoute('task_index');
                }

                $taskManager->applyTemplate($newTask, $taskTemplate);
            }

            $entityManager->persist($newTask);
            $entityManager->flush();

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
                'direction' => 'desc',
            ]
        );

        $filterForm = $this->createIndexFilterForm($formFactory);

        return $this->render('task/index.html.twig', [
           'pagination' => $pagination,
           'filterForm' => $filterForm,
           'form' => $form,
       ]);
    }

    #[Route('/today/task/active', name: 'task_active')]
    public function active(Request $request, TaskRepository $taskRepository, PaginatorInterface $paginator): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $queryBuilder = $taskRepository->findActiveTasks($this->getUser());

        $pagination = $this->populatePaginationData(
            $request,
            $paginator,
            $queryBuilder,
            [
                'sort' => 'task.createdAt',
                'direction' => 'desc',
            ]
        );

        $form = $this->createForm(ActionTaskFormType::class, new ActionTaskModel(), [
            'action' => $this->generateUrl('task_form_complete'),
        ]);

        return $this->render('task/active.html.twig', [
            'pagination' => $pagination,
            'form' => $form,
        ]);
    }

    #[Route('/task/{id}/view', name: 'task_view')]
    public function view(
        Request $request,
        EntityManagerInterface $entityManager,
        TaskRepository $taskRepository,
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
            $task->setTemplate($data->isTemplate());
            $task->setPriority($data->getPriority());
            $task->setTimeEstimate($data->getTimeEstimate());
            $task->setActive($data->isActive());

            $entityManager->flush();

            $this->addFlash('success', 'Task successfully updated');

            return $this->redirectToRoute('task_index');
        }

        return $this->render(
            'task/view.html.twig',
            [
                'task' => $task,
                'form' => $form,
                'subtasks' => $taskRepository->findForParent($task)->getQuery()->getResult(),
            ]
        );
    }

    #[Route('/task/{id}/partial_edit', name: 'task_partial_edit', methods: ['GET', 'POST'])]
    public function partialEdit(
        Request $request,
        EntityManagerInterface $entityManager,
        TagManager $tagManager,
        TaskRepository $taskRepository,
        string $id): Response
    {
        $task = $taskRepository->findOrException($id);

        $form = $this->createForm(
            EditTaskPartialFormType::class,
            EditTaskPartialModel::fromEntity($task),
            [
                'action' => $this->generateUrl('task_partial_edit', ['id' => $id]),
                'timezone' => $this->getUser()->getTimezone(),
            ]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var EditTaskPartialModel $data */
            $data = $form->getData();

            $task->setName($data->getName());
            $task->setDescription($data->getDescription());
            $task->setPriority($data->getPriority());
            $task->setCompletedAt($data->getCompletedAt());
            $task->setDueAt($data->getDueAt());
            $task->setTimeEstimate($data->getTimeEstimate());
            $task->setActive($data->isActive());

            foreach ($task->getTagLinks() as $tagLink) {
                $entityManager->remove($tagLink);
            }

            if (null !== $data->getTags()) {
                $tagNames = explode(',', $data->getTags());
                foreach ($tagNames as $tagName) {
                    $tag = $tagManager->findOrCreateByName($tagName, $task->getAssignedTo());
                    $tagLink = new TagLink($task, $tag);
                    $entityManager->persist($tagLink);
                }
            }

            $entityManager->flush();

            $this->addFlash('success', "Task '{$task->getName()}' updated");

            return new Response(null, Response::HTTP_FOUND, [
                'Turbo-Location' => $request->headers->get('referer'),
            ]);
        }

        return $this->render('task/partials/_edit.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/task/{id}/lineage', name: 'task_lineage', methods: ['GET'])]
    public function getLineage(TaskRepository $taskRepository, string $id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $task = $taskRepository->findOrException($id);
        if (!$task->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $lineage = $task->getLineage();

        return $this->render('task/partials/_breadcrumbs.html.twig', [
            'tasks' => $lineage,
        ]);
    }

    #[Route('/task/{id}/complete', name: 'task_complete')]
    public function complete(
        Request $request,
        EntityManagerInterface $entityManager,
        TaskRepository $taskRepository,
        string $id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $task = $taskRepository->findOrException($id);
        if (!$task->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        if ($task->isTemplate()) {
            $this->addFlash('danger', 'Can not complete template tasks');

            return $this->redirectToRoute('task_index');
        }

        $completed = 'complete' === $request->query->get('value', 'complete');
        if ($completed && !$task->completed()) {
            $task->complete();
        } elseif (!$completed && $task->completed()) {
            $task->clearCompleted();
        }

        $entityManager->flush();

        $this->addFlash('success', "Completed '{$task->getName()}'");

        return $this->redirectToRoute('task_index');
    }

    #[Route('/task/complete', name: 'task_form_complete')]
    public function formComplete(
        Request $request,
        EntityManagerInterface $entityManager,
        TaskRepository $taskRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $form = $this->createForm(ActionTaskFormType::class, new ActionTaskModel());
        $redirectTo = $request->request->get('redirect', 'task_active');

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var ActionTaskModel $data */
            $data = $form->getData();

            $task = $taskRepository->findOrException($data->getTaskId());
            if (!$task->isAssignedTo($this->getUser())) {
                throw $this->createAccessDeniedException();
            }

            if ('complete' !== $data->getAction()) {
                throw new LogicException('Only complete is supported');
            }

            if ($task->isTemplate()) {
                $this->addFlash('danger', 'Can not complete template tasks');

                return $this->redirectToRoute($redirectTo);
            }

            $completed = 'true' === $data->getValue();
            if ($completed && !$task->completed()) {
                $task->complete();
            } elseif (!$completed && $task->completed()) {
                $task->clearCompleted();
            }

            $entityManager->flush();
            $this->addFlash('success', "Completed '{$task->getName()}'");
        }

        return $this->redirectToRoute($redirectTo);
    }

    #[Route('/task/{id}/close', name: 'task_close')]
    public function close(
        Request $request,
        EntityManagerInterface $entityManager,
        TaskRepository $taskRepository,
        string $id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $task = $taskRepository->findOrException($id);
        if (!$task->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        if ($task->isTemplate()) {
            $this->addFlash('danger', 'Can not close template tasks');

            return $this->redirectToRoute('task_index');
        }

        $closed = 'close' === $request->query->get('value', 'closed');
        if ($closed && !$task->closed()) {
            $task->close();
        } elseif (!$closed && $task->closed()) {
            $task->clearClosed();
        }

        $entityManager->flush();

        $action = $closed ? 'Closed' : 'Re-opened';
        $this->addFlash('success', "$action '{$task->getName()}'");

        return $this->redirectToRoute('task_index');
    }

    #[Route('/task/{id}/delete', name: 'task_delete')]
    public function remove(
        TaskRepository $taskRepository,
        EntityManagerInterface $entityManager,
        string $id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $task = $taskRepository->findOrException($id);
        if (!$task->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $now = new DateTime('now');

        $task->softDelete($now);
        $parentIds = [$task->getIdString()];
        while (0 !== count($parentIds)) {
            $subtasks = $taskRepository->findByKeys('parent', $parentIds);
            $parentIds = [];

            foreach ($subtasks as $subtask) {
                $parentIds[] = $subtask->getIdString();
                $subtask->softDelete($now);
            }
        }

        $entityManager->flush();

        $this->addFlash('success', "Task '{$task->getName()}' and it's children have been deleted");

        return $this->redirectToRoute('task_index');
    }

    #[Route('/task/{id}/check', name: 'task_check')]
    public function check(Request $request, string $id, TaskRepository $taskRepository, EntityManagerInterface $entityManager): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $task = $taskRepository->findOrException($id);
        if (!$task->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }


        if ($task->isTemplate()) {
            $this->addFlash('task-error', 'Can not delete a template');
            return $this->redirect($request->headers->get('referer'));
        }

        $completed = $request->getPayload()->get('checked') !== null;

        if ($completed) {
            $task->complete();
        } else {
            $task->clearCompleted();
        }

        $entityManager->flush();

        return $this->redirect($request->headers->get('referer'));
    }
}

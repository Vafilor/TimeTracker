<?php

declare(strict_types=1);

namespace App\Controller;

use App\Api\ApiTask;
use App\Entity\Task;
use App\Form\Model\TaskListFilterModel;
use App\Form\Model\TaskModel;
use App\Form\TaskFormType;
use App\Form\TaskListFilterFormType;
use App\Repository\TaskRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TaskController extends BaseController
{
    #[Route('/task', name: 'task_list')]
    public function list(
        Request $request,
        TaskRepository $taskRepository,
        FormFactoryInterface $formFactory,
        PaginatorInterface $paginator
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $queryBuilder = $taskRepository->findByUserQueryBuilder($this->getUser());

        $filterForm = $formFactory->createNamed('',
        TaskListFilterFormType::class,
            new TaskListFilterModel(), [
            'csrf_protection' => false,
            'method' => 'GET',
            'allow_extra_fields' => true
        ]);

        $filterForm->handleRequest($request);
        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            /** @var TaskListFilterModel $data */
            $data = $filterForm->getData();

            if ($data->hasName()) {
                $queryBuilder->andWhere('task.name LIKE :name')
                             ->setParameter('name', "%{$data->getName()}%")
                ;
            }

            if ($data->hasDescription()) {
                $queryBuilder->andWhere('task.description LIKE :description')
                             ->setParameter('description', "%{$data->getDescription()}%")
                ;
            }

            if (!$data->getShowCompleted()) {
                $queryBuilder->andWhere('task.completedAt IS NULL');
            }
        } else {
            $queryBuilder->andWhere('task.completedAt IS NULL');
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

    #[Route('/json/task', name: 'task_json_list')]
    public function jsonList(
        Request $request,
        TaskRepository $taskRepository,
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $queryBuilder = $taskRepository->findByUserQueryBuilder($this->getUser())
                                       ->setMaxResults(15)
                                       ->orderBy('task.createdAt', 'DESC')
        ;

        $name = $request->query->get('name');
        if (!is_null($name))
        {
            $queryBuilder = $queryBuilder->andWhere('task.name LIKE :name')
                                         ->setParameter('name', "%$name%")
            ;
        }

        /** @var Task[] $results */
        $tasks = $queryBuilder->getQuery()->getResult();

        $apiTasks = array_map(
            fn($task) => ApiTask::fromEntity($task, $this->getUser()),
            $tasks
        );

        return $this->json($apiTasks);
    }

    #[Route('/task/create', name: 'task_create')]
    public function create(
        Request $request
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $task = new TaskModel();

        $form = $this->createForm(
            TaskFormType::class,
            $task,
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
        TaskRepository $taskRepository,
        string $id
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $task = $taskRepository->find($id);
        if (is_null($task)) {
            throw $this->createNotFoundException();
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
        TaskRepository $taskRepository,
        string $id
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $task = $taskRepository->find($id);
        if (is_null($task)) {
            return $this->json([], Response::HTTP_NOT_FOUND);
        }

        if (!$task->getCreatedBy()->equalIds($this->getUser())) {
            return $this->json([], Response::HTTP_FORBIDDEN);
        }

        $completed = true;
        $data = json_decode($request->getContent(), true);
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
    public function jsonUpdate(Request $request, TaskRepository $taskRepository, string $id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        /** @var Task|null $task */
        $task = $taskRepository->find($id);
        if (is_null($task)) {
            throw $this->createNotFoundException();
        }

        $data = json_decode($request->getContent(), true);

        if (array_key_exists('description', $data)) {
            $task->setDescription($data['description']);
        }

        $this->getDoctrine()->getManager()->flush();

        $apiTask = ApiTask::fromEntity($task, $this->getUser());

        return $this->json($apiTask);
    }
}

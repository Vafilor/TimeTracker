<?php

declare(strict_types=1);

namespace App\Controller;

use App\Api\ApiTask;
use App\Entity\Task;
use App\Form\Model\TaskModel;
use App\Form\TaskFormType;
use App\Repository\TaskRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TaskController extends BaseController
{
    #[Route('/task', name: 'task_list')]
    public function list(
        Request $request,
        TaskRepository $taskRepository,
        PaginatorInterface $paginator
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $queryBuilder = $taskRepository->findByUserQueryBuilder($this->getUser());

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

        $name = $request->request->get('name', null);
        if (!is_null($name))
        {
            $queryBuilder = $queryBuilder->andWhere('task.name LIKE :name')
                                         ->setParameter('name', $name)
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
            $this->addFlash('danger', "Task not found");
            return $this->redirectToRoute('task_list');
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
}

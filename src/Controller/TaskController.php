<?php

declare(strict_types=1);

namespace App\Controller;

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
    #[Route('/task/list', name: 'task_list')]
    public function index(
        Request $request,
        TaskRepository $taskRepository,
        PaginatorInterface $paginator): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $queryBuilder = $taskRepository->findByUserQueryBuilder($this->getUser());

        $pagination = $this->populatePaginationData($request, $paginator, $queryBuilder, [
            'sort' => 'task.createdAt',
            'direction' => 'desc'
        ]);

        return $this->render('task/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/task/create', name: 'task_create')]
    public function create(Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $task = new TaskModel();

        $form = $this->createForm(TaskFormType::class, $task);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid())
        {
            /** @var TaskModel $data */
            $data = $form->getData();

            $newTask = new Task($this->getUser(), $data->getName());
            $newTask->setDescription($data->getDescription());

            $manager = $this->getDoctrine()->getManager();
            $manager->persist($newTask);
            $manager->flush();
        }

        return $this->render('task/create.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/task/{id}/view', name: 'task_view')]
    public function view(Request $request, TaskRepository $taskRepository, string $id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $task = $taskRepository->find($id);
        if (is_null($task)) {
            $this->addFlash('danger', "Task not found");
            return $this->redirectToRoute('task_list');
        }

        $taskModel = TaskModel::fromEntity($task);

        $form = $this->createForm(TaskFormType::class, $taskModel, [
            'timezone' => $this->getUser()->getTimezone()
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid())
        {
            /** @var TaskModel $data */
            $data = $form->getData();

            $task->setName($data->getName());
            $task->setDescription($data->getDescription());

            $this->getDoctrine()->getManager()->flush();
        }

        return $this->render('task/view.html.twig', [
            'task' => $task,
            'form' => $form->createView()
        ]);
    }
}

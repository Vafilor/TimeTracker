<?php

declare(strict_types=1);

namespace App\Controller;

use App\Api\ApiTask;
use App\Form\Model\TaskModel;
use App\Form\TaskFormType;
use App\Repository\TaskRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApiTaskController extends BaseController
{
    #[Route('/api/task/{id}/view', name: 'api_task_view')]
    public function view(
        Request $request,
        TaskRepository $taskRepository,
        string $id
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $task = $taskRepository->findOrException($id);
        $apiTask = ApiTask::fromEntity($task, $this->getUser());

        return $this->json($apiTask);
    }
}

<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\TaskRepository;
use App\Util\DateTimeUtil;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ApiReportController extends BaseController
{
    #[Route('/json/report/task/{taskId}', name: 'json_report_task_entry', methods: ['GET'])]
    public function taskReport(Request $request, TaskRepository $taskRepository, string $taskId): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $task = $taskRepository->findOrException($taskId);
        if (!$task->isAssignedTo($this->getUser())) {
            throw $this->createAccessDeniedException();
        }

        $totalSeconds = $taskRepository->getTotalTimeInSeconds($task);
        $diff = DateTimeUtil::dateIntervalFromSeconds($totalSeconds);
        $formattedTime = $diff->format($this->getUser()->getDurationFormat());

        return $this->jsonNoNulls([
            'totalTime' => $formattedTime,
            'totalSeconds' => $totalSeconds,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Manager;

use App\Entity\Task;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use SplStack;

class TaskManager
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function applyTemplate(Task $task, Task $taskTemplate)
    {
        $stack = new SplStack();
        foreach ($taskTemplate->getSubtasks() as $subtask) {
            $copy = clone $subtask;
            $copy->setId(Uuid::uuid4());
            $copy->setParent($task);
            $copy->setTemplate(false);
            $stack->push($copy);
            $this->entityManager->persist($copy);
        }

        while (!$stack->isEmpty()) {
            /** @var Task $newTask */
            $newTask = $stack->pop();

            foreach ($newTask->getSubtasks() as $subtask) {
                $copy = clone $subtask;
                $copy->setId(Uuid::uuid4());
                $copy->setParent($newTask);
                $copy->setTemplate(false);
                $stack->push($copy);
                $this->entityManager->persist($copy);
            }
        }
    }
}

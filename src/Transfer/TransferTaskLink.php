<?php

declare(strict_types=1);

namespace App\Transfer;

use App\Entity\Task;

class TransferTaskLink
{
    public string $id;

    public string $name;

    public string $assignedTo;

    public static function fromTask(Task $task): TransferTaskLink
    {
        return new TransferTaskLink($task->getIdString(), $task->getName(), $task->getAssignedTo()->getUsername());
    }

    public function __construct(string $id, string $name, string $assignedTo)
    {
        $this->id = $id;
        $this->name = $name;
        $this->assignedTo = $assignedTo;
    }
}

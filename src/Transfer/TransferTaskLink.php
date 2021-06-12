<?php

declare(strict_types=1);

namespace App\Transfer;

use App\Entity\Task;

class TransferTaskLink
{
    public string $name;
    public string $createdBy;

    /**
     * @param Task $task
     * @return TransferTaskLink
     */
    public static function fromTask(Task $task): TransferTaskLink
    {
        return new TransferTaskLink($task->getName(), $task->getCreatedBy()->getUsername());
    }

    public function __construct(string $name, string $createdBy)
    {
        $this->name = $name;
        $this->createdBy = $createdBy;
    }
}

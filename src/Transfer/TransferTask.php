<?php

declare(strict_types=1);

namespace App\Transfer;

use App\Entity\Task;
use App\Entity\User;
use App\Util\DateTimeUtil;
use Ramsey\Uuid\Uuid;

class TransferTask
{
    public string $id;
    public int $createdAt;
    public int $updatedAt;
    public string $name;
    public string $createdBy;
    public string $description;
    public ?int $completedAt = null;

    public static function fromEntity(Task $task): TransferTask
    {
        $transfer = new TransferTask();

        $transfer->id = $task->getIdString();
        $transfer->createdAt = $task->getCreatedAt()->getTimestamp();
        $transfer->updatedAt = $task->getUpdatedAt()->getTimestamp();
        $transfer->name = $task->getName();
        $transfer->createdBy = $task->getCreatedBy()->getUsername();
        $transfer->description = $task->getDescription();

        if ($task->completed()) {
            $transfer->completedAt = $task->getCompletedAt()->getTimestamp();
        }

        return $transfer;
    }

    /**
     * @param Task[]|iterable $entities
     * @return TransferTask[]
     */
    public static function fromEntities(iterable $entities): array
    {
        $items = [];
        foreach ($entities as $entity) {
            $items[] = self::fromEntity($entity);
        }

        return $items;
    }

    public function toEntity(User $createdBy): Task
    {
        $task = new Task($createdBy, $this->name);

        $task->setId(Uuid::fromString($this->id));
        $task->setCreatedAt(DateTimeUtil::dateFromTimestamp($this->createdAt));
        $task->setUpdatedAt(DateTimeUtil::dateFromTimestamp($this->updatedAt));
        $task->setDescription($this->description);

        if ($this->completedAt) {
            $task->setCompletedAt(DateTimeUtil::dateFromTimestamp($this->completedAt));
        }

        return $task;
    }
}

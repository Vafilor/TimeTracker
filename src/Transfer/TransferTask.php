<?php

declare(strict_types=1);

namespace App\Transfer;

use App\Entity\Task;

class TransferTask
{
    public int $createdAt;
    public int $updatedAt;
    public string $name;
    public string $createdBy;
    public string $description;
    public ?int $completedAt = null;

    public static function fromEntity(Task $task): TransferTask
    {
        $transfer = new TransferTask();

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
}
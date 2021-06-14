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
    public string $canonicalName; // Technically not needed, but handy to see in json.
    public string $assignedTo;
    public string $description;
    public ?int $completedAt = null;
    public int $priority;

    /**
     * @var TransferTagLink[]
     */
    public array $tags = [];

    public static function fromEntity(Task $task): TransferTask
    {
        $transfer = new TransferTask();

        $transfer->id = $task->getIdString();
        $transfer->createdAt = $task->getCreatedAt()->getTimestamp();
        $transfer->updatedAt = $task->getUpdatedAt()->getTimestamp();
        $transfer->name = $task->getName();
        $transfer->assignedTo = $task->getAssignedTo()->getUsername();
        $transfer->description = $task->getDescription();
        $transfer->priority = $task->getPriority();
        $transfer->canonicalName = $task->getCanonicalName();

        if ($task->completed()) {
            $transfer->completedAt = $task->getCompletedAt()->getTimestamp();
        }

        $transfer->tags = TransferTagLink::fromTags($task->getTags());

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

    public function toEntity(User $assignedTo): Task
    {
        // No need to set canonical name as that is automatically handled by the class via setting the name.
        $task = new Task($assignedTo, $this->name);

        $task->setId(Uuid::fromString($this->id));
        $task->setCreatedAt(DateTimeUtil::dateFromTimestamp($this->createdAt));
        $task->setUpdatedAt(DateTimeUtil::dateFromTimestamp($this->updatedAt));
        $task->setDescription($this->description);
        $task->setPriority($this->priority);

        if ($this->completedAt) {
            $task->setCompletedAt(DateTimeUtil::dateFromTimestamp($this->completedAt));
        }

        return $task;
    }
}

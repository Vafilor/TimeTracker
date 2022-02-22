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
    public ?int $deletedAt = null;
    public ?int $dueAt = null;
    public string $name;
    public string $canonicalName; // Technically not needed, but handy to see in json.
    public string $assignedTo;
    public string $description;
    public ?int $completedAt = null;
    public int $priority;
    public ?string $parentId = null;
    public ?int $timeEstimate = null;

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
        $transfer->timeEstimate = $task->getTimeEstimate();

        if ($task->isDeleted()) {
            $transfer->deletedAt = $task->getDeletedAt()->getTimestamp();
        }

        if ($task->completed()) {
            $transfer->completedAt = $task->getCompletedAt()->getTimestamp();
        }

        if (!is_null($task->getDueAt())) {
            $transfer->dueAt = $task->getDueAt()->getTimestamp();
        }

        if ($task->hasParent()) {
            $transfer->parentId = $task->getParent()->getIdString();
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
        $task->setTimeEstimate($this->timeEstimate);

        if ($this->completedAt) {
            $task->setCompletedAt(DateTimeUtil::dateFromTimestamp($this->completedAt));
        }

        if ($this->dueAt) {
            $task->setDueAt(DateTimeUtil::dateFromTimestamp($this->dueAt));
        }

        if ($this->deletedAt) {
            $task->softDelete(DateTimeUtil::dateFromTimestamp($this->deletedAt));
        }

        return $task;
    }
}
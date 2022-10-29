<?php

declare(strict_types=1);

namespace App\Transfer;

use App\Entity\TimeEntry;
use App\Entity\User;
use App\Util\DateTimeUtil;
use Ramsey\Uuid\Uuid;

class TransferTimeEntry
{
    public string $id;
    public int $createdAt;
    public int $updatedAt;
    public int $startedAt;
    public ?int $endedAt = null;
    public ?int $deletedAt = null;
    public string $description;
    public ?TransferTaskLink $task = null;
    public string $assignedTo;

    /**
     * @var TransferTagLink[]
     */
    public array $tags = [];

    public static function fromEntity(TimeEntry $timeEntry): TransferTimeEntry
    {
        $transfer = new TransferTimeEntry();

        $transfer->id = $timeEntry->getIdString();
        $transfer->createdAt = $timeEntry->getCreatedAt()->getTimestamp();
        $transfer->updatedAt = $timeEntry->getUpdatedAt()->getTimestamp();
        $transfer->startedAt = $timeEntry->getStartedAt()->getTimestamp();
        $transfer->description = $timeEntry->getDescription();
        $transfer->assignedTo = $timeEntry->getAssignedTo()->getUsername();

        if ($timeEntry->isOver()) {
            $transfer->endedAt = $timeEntry->getEndedAt()->getTimestamp();
        }

        if ($timeEntry->isDeleted()) {
            $transfer->deletedAt = $timeEntry->getDeletedAt()->getTimestamp();
        }

        if ($timeEntry->assignedToTask()) {
            $transfer->task = TransferTaskLink::fromTask($timeEntry->getTask());
        }

        $transfer->tags = TransferTagLink::fromTags($timeEntry->getTags());

        return $transfer;
    }

    /**
     * @param TimeEntry[]|iterable $entities
     *
     * @return TransferTimeEntry[]
     */
    public static function fromEntities(iterable $entities): array
    {
        $items = [];
        foreach ($entities as $entity) {
            $items[] = self::fromEntity($entity);
        }

        return $items;
    }

    public function toEntity(User $assignedTo): TimeEntry
    {
        $entity = new TimeEntry($assignedTo, DateTimeUtil::dateFromTimestamp($this->createdAt));
        $entity->setId(Uuid::fromString($this->id));
        $entity->setUpdatedAt(DateTimeUtil::dateFromTimestamp($this->updatedAt));
        $entity->setStartedAt(DateTimeUtil::dateFromTimestamp($this->startedAt));

        if ($this->endedAt) {
            $entity->setEndedAt(DateTimeUtil::dateFromTimestamp($this->endedAt));
        }

        if ($this->deletedAt) {
            $entity->softDelete(DateTimeUtil::dateFromTimestamp($this->deletedAt));
        }

        $entity->setDescription($this->description);

        return $entity;
    }
}

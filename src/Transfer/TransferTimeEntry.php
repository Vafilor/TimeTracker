<?php

declare(strict_types=1);

namespace App\Transfer;

use App\Entity\TimeEntry;

class TransferTimeEntry
{
    public int $createdAt;
    public int $updatedAt;
    public int $startedAt;
    public ?int $endedAt = null;
    public ?int $deletedAt = null;
    public string $description;
    public TransferTaskLink $task;
    public string $createdBy;

    /**
     * @var TransferTagLink[]
     */
    public array $tags = [];

    public static function fromEntity(TimeEntry $timeEntry): TransferTimeEntry
    {
        $transfer = new TransferTimeEntry();

        $transfer->createdAt = $timeEntry->getCreatedAt()->getTimestamp();
        $transfer->updatedAt = $timeEntry->getUpdatedAt()->getTimestamp();
        $transfer->startedAt = $timeEntry->getStartedAt()->getTimestamp();
        $transfer->description = $timeEntry->getDescription();
        $transfer->createdBy = $timeEntry->getOwner()->getUsername();

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
}

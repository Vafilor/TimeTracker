<?php

declare(strict_types=1);

namespace App\Api;

use App\Entity\TimeEntry;
use App\Entity\User;

class ApiTimeEntry
{
    public string $id;
    public string $createdAt;
    public string $updatedAt;
    public int $updatedAtEpoch;
    public string $startedAt;
    public int $startedAtEpoch;
    public ?string $endedAt = null;
    public ?int $endedAtEpoch = null;
    public string $description;
    public ?string $duration = null;
    public string $taskId;
    public ?string $url = null;
    public array $tags;

    /**
     * @param TimeEntry $timeEntry
     * @param User $user
     * @param string $format 'date' | 'today'
     * @throws \Exception
     * @return ApiTimeEntry
     */
    public static function fromEntity(TimeEntry $timeEntry, User $user, string $format = 'date'): ApiTimeEntry
    {
        $apiTimeEntry = new ApiTimeEntry();
        $apiTimeEntry->id = $timeEntry->getIdString();
        $apiTimeEntry->createdAt = ApiDateTime::formatUserDate($timeEntry->getCreatedAt(), $user, $format);
        $apiTimeEntry->startedAt = ApiDateTime::formatUserDate($timeEntry->getStartedAt(), $user, $format);
        $apiTimeEntry->startedAtEpoch = $timeEntry->getStartedAt()->getTimestamp();
        $apiTimeEntry->updatedAt = ApiDateTime::formatUserDate($timeEntry->getUpdatedAt(), $user, $format);
        $apiTimeEntry->updatedAtEpoch = $timeEntry->getUpdatedAt()->getTimestamp();
        $apiTimeEntry->description = $timeEntry->getDescription();
        // TODO does this make another db call?
        if ($timeEntry->assignedToTask()) {
            $apiTimeEntry->taskId = $timeEntry->getTask()->getIdString();
        }

        if ($timeEntry->isOver()) {
            $apiTimeEntry->endedAt = ApiDateTime::formatUserDate($timeEntry->getEndedAt(), $user, $format);
            $apiTimeEntry->endedAtEpoch = $timeEntry->getEndedAt()->getTimestamp();
            $apiTimeEntry->duration = $timeEntry->duration()->format($user->getDurationFormat());
        }

        $apiTimeEntry->tags = ApiTag::fromEntities($timeEntry->getTags());

        return $apiTimeEntry;
    }

    /**
     * @param TimeEntry[] $entities
     * @param User $user
     * @param string $format
     * @return ApiTimeEntry[]
     * @throws \Exception
     */
    public static function fromEntities(iterable $entities, User $user, string $format = 'date'): array
    {
        $items = [];
        foreach ($entities as $entity) {
            $items[] = self::fromEntity($entity, $user, $format);
        }

        return $items;
    }
}

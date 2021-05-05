<?php

declare(strict_types=1);

namespace App\Api;

use App\Entity\TimeEntry;
use App\Entity\User;
use DateTime;
use DateTimeZone;

class ApiTimeEntry
{
    public string $createdAt;
    public string $updatedAt;
    public string $startedAt;
    public int $startedAtEpoch;
    public string $endedAt;
    public int $endedAtEpoch;
    public string $description;
    public string $duration;
    public array $apiTags;

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
        $apiTimeEntry->createdAt = ApiDateTime::formatUserDate($timeEntry->getCreatedAt(), $user, $format);
        $apiTimeEntry->startedAt = ApiDateTime::formatUserDate($timeEntry->getStartedAt(), $user, $format);
        $apiTimeEntry->startedAtEpoch = $timeEntry->getStartedAt()->getTimestamp();
        $apiTimeEntry->updatedAt = ApiDateTime::formatUserDate($timeEntry->getUpdatedAt(), $user, $format);
        $apiTimeEntry->description = $timeEntry->getDescription();

        if ($timeEntry->isOver()) {
            $apiTimeEntry->endedAt = ApiDateTime::formatUserDate($timeEntry->getEndedAt(), $user, $format);
            $apiTimeEntry->endedAtEpoch = $timeEntry->getEndedAt()->getTimestamp();
            $apiTimeEntry->duration = $timeEntry->duration()->format($user->getDurationFormat());
        }

        $tags = array_map(
            fn($timeEntryTag) => $timeEntryTag->getTag(),
            $timeEntry->getTimeEntryTags()->toArray()
        );

        $apiTags = array_map(
            fn($tag) => ApiTag::fromEntity($tag),
            $tags
        );

        $apiTimeEntry->apiTags = $apiTags;

        return $apiTimeEntry;
    }

    public function __construct() {
    }
}

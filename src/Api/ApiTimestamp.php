<?php

declare(strict_types=1);

namespace App\Api;

use App\Entity\TimeEntry;
use App\Entity\Timestamp;
use App\Entity\User;
use DateTime;
use Knp\Bundle\TimeBundle\DateTimeFormatter;

class ApiTimestamp
{
    public string $id;
    public string $createdAt;
    public int $createdAtEpoch;
    public ?string $createdAgo; // human friendly string of how long ago the timestamp was created. e.g. '5 seconds ago'
    public array $tags;

    public static function fromEntity(
        DateTimeFormatter $dateTimeFormatter,
        Timestamp $timestamp,
        User $user,
        DateTime $now,
        string $format = 'date'
    ): ApiTimestamp {
        $apiModel = new ApiTimestamp();
        $apiModel->id = $timestamp->getIdString();
        $apiModel->createdAt = ApiDateTime::formatUserDate($timestamp->getCreatedAt(), $user, $format);
        $apiModel->createdAtEpoch = $timestamp->getCreatedAt()->getTimestamp();
        $apiModel->createdAgo = $dateTimeFormatter->formatDiff($timestamp->getCreatedAt(), $now);
        
        $apiTags = array_map(
            fn ($tag) => ApiTag::fromEntity($tag),
            $timestamp->getTags()
        );

        $apiModel->tags = $apiTags;

        return $apiModel;
    }

    /**
     * @param Timestamp[] $entities
     * @param DateTimeFormatter $dateTimeFormatter
     * @param User $user
     * @param DateTime $now
     * @param string $format
     * @return array
     */
    public static function fromEntities(
        iterable $entities,
        DateTimeFormatter $dateTimeFormatter,
        User $user,
        DateTime $now,
        string $format = 'date'
    ): array {
        $items = [];
        foreach ($entities as $entity) {
            $items[] = self::fromEntity($dateTimeFormatter, $entity, $user, $now, $format);
        }

        return $items;
    }
}

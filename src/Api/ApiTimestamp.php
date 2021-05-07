<?php

declare(strict_types=1);

namespace App\Api;

use App\Entity\Timestamp;
use App\Entity\User;
use DateTime;
use Knp\Bundle\TimeBundle\DateTimeFormatter;

class ApiTimestamp
{
    public string $id;
    public string $createdAt;
    public ?string $createdAgo; // human friendly string of how long ago the timestamp was created. e.g. '5 seconds ago'
    public array $tags;

    public static function fromEntity(
        DateTimeFormatter $dateTimeFormatter,
        Timestamp $timestamp,
        User $user,
        DateTime $now,
        string $format = 'date'): ApiTimestamp
    {
        $apiModel = new ApiTimestamp();
        $apiModel->id = $timestamp->getIdString();
        $apiModel->createdAt = ApiDateTime::formatUserDate($timestamp->getCreatedAt(), $user, $format);
        $apiModel->createdAgo = $dateTimeFormatter->formatDiff($now, $timestamp->getCreatedAt());

        $tags = array_map(
            fn($timestampTag) => $timestampTag->getTag(),
            $timestamp->getTimestampTags()->toArray()
        );

        $apiTags = array_map(
            fn($tag) => ApiTag::fromEntity($tag),
            $tags
        );

        $apiModel->tags = $apiTags;

        return $apiModel;
    }

    public function __construct() {
    }
}
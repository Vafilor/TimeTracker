<?php

declare(strict_types=1);

namespace App\Api;

use App\Entity\Note;
use App\Entity\User;
use App\Util\DateFormatType;

class ApiNote
{
    public string $id;
    public string $title;
    public string $content;
    public string $createdAt;
    public int $createdAtEpoch;
    public array $tags;
    public ?string $url;

    public static function fromEntity(Note $note, User $user, string $format = DateFormatType::DATE_TIME): self
    {
        $apiNote = new ApiNote();
        $apiNote->title = $note->getTitle();
        $apiNote->content = $note->getContent();
        $apiNote->createdAt = ApiDateTime::formatUserDate($note->getCreatedAt(), $user, $format);
        $apiNote->createdAtEpoch = $note->getCreatedAt()->getTimestamp();
        $apiNote->tags = ApiTag::fromEntities($note->getTags());

        return $apiNote;
    }

    /**
     * @param Note[]|iterable $entities
     *
     * @return ApiNote[]
     */
    public static function fromEntities(iterable $entities, User $user, string $format = DateFormatType::DATE_TIME): array
    {
        $items = [];
        foreach ($entities as $entity) {
            $items[] = self::fromEntity($entity, $user, $format);
        }

        return $items;
    }
}

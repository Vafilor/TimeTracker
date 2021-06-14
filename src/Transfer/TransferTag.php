<?php

declare(strict_types=1);

namespace App\Transfer;

use App\Entity\Tag;
use App\Entity\User;
use App\Util\DateTimeUtil;
use Ramsey\Uuid\Uuid;

class TransferTag
{
    public string $id;
    public int $createdAt;
    public string $name;
    public string $canonicalName; // Technically not needed, but handy to see in json.
    public string $color;
    public string $assignedTo;

    public static function fromEntity(Tag $tag): TransferTag
    {
        $transfer = new TransferTag();

        $transfer->id = $tag->getIdString();
        $transfer->createdAt = $tag->getCreatedAt()->getTimestamp();
        $transfer->name = $tag->getName();
        $transfer->canonicalName = $tag->getCanonicalName();
        $transfer->color = $tag->getColor();
        $transfer->assignedTo = $tag->getAssignedTo()->getUsername();

        return $transfer;
    }

    /**
     * @param Tag[]|iterable $entities
     * @return TransferTag[]
     */
    public static function fromEntities(iterable $entities): array
    {
        $items = [];
        foreach ($entities as $entity) {
            $items[] = self::fromEntity($entity);
        }

        return $items;
    }

    public function toEntity(User $assignedTo): Tag
    {
        // No need to set canonical name as that is automatically handled by the class via setting the name.
        $tag = new Tag($assignedTo, $this->name, $this->color, DateTimeUtil::dateFromTimestamp($this->createdAt));
        $tag->setId(Uuid::fromString($this->id));

        return $tag;
    }
}

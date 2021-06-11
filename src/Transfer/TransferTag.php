<?php

declare(strict_types=1);

namespace App\Transfer;

use App\Entity\Tag;

class TransferTag
{
    public int $createdAt;
    public string $name;
    public string $color;
    public string $createdBy;

    public static function fromEntity(Tag $tag): TransferTag
    {
        return new TransferTag(
            $tag->getCreatedAt()->getTimestamp(),
            $tag->getName(),
            $tag->getColor(),
            $tag->getCreatedBy()->getUsername()
        );
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

    public function __construct(int $createdAt, string $name, string $color, string $createdBy)
    {
        $this->createdAt = $createdAt;
        $this->name = $name;
        $this->color = $color;
        $this->createdBy = $createdBy;
    }
}

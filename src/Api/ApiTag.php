<?php

declare(strict_types=1);

namespace App\Api;

use App\Entity\Tag;
use App\Entity\TagLink;

class ApiTag
{
    public static function fromEntity(Tag $tag): ApiTag
    {
        return new ApiTag($tag->getName(), $tag->getCanonicalName(), $tag->getColor());
    }

    /**
     * @param Tag[]|iterable $entities
     *
     * @return ApiTag[]
     */
    public static function fromEntities(iterable $entities): array
    {
        $items = [];
        foreach ($entities as $entity) {
            $items[] = self::fromEntity($entity);
        }

        return $items;
    }

    /**
     * @param TagLink[]|iterable $entities
     *
     * @return ApiTag[]
     */
    public static function fromTagLinks(iterable $entities): array
    {
        $items = [];
        foreach ($entities as $entity) {
            $items[] = self::fromEntity($entity->getTag());
        }

        return $items;
    }

    public function __construct(public string $name, string $canonicalName, public string $color)
    {
    }
}

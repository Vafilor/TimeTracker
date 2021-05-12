<?php

declare(strict_types=1);

namespace App\Api;

use App\Entity\Tag;
use App\Entity\Task;
use App\Entity\User;

class ApiTag
{
    public string $name;
    public string $color;

    public static function fromEntity(Tag $tag): ApiTag
    {
        return new ApiTag($tag->getName(), $tag->getColor());
    }

    /**
     * @param Tag[] $entities
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

    public function __construct(string $name, string $color)
    {
        $this->name = $name;
        $this->color = $color;
    }
}

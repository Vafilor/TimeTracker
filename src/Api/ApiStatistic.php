<?php

declare(strict_types=1);

namespace App\Api;

use App\Entity\Statistic;

class ApiStatistic
{
    public string $name;

    public static function fromEntity(Statistic $statistic): ApiStatistic
    {
        return new ApiStatistic($statistic->getName());
    }

    /**
     * @param Statistic[]|iterable $entities
     * @return ApiStatistic[]
     */
    public static function fromEntities(iterable $entities): array
    {
        $items = [];
        foreach ($entities as $entity) {
            $items[] = self::fromEntity($entity);
        }

        return $items;
    }

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}

<?php

declare(strict_types=1);

namespace App\Api;

use App\Entity\Statistic;
use App\Entity\StatisticValue;

class ApiStatisticValue
{
    public string $id;
    public string $name;
    public float $value;

    public static function fromEntity(StatisticValue $statisticValue): ApiStatisticValue
    {
        return new ApiStatisticValue(
            $statisticValue->getIdString(),
            $statisticValue->getStatistic()->getName(),
            $statisticValue->getValue()
        );
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

    public function __construct(string $id, string $name, float $value)
    {
        $this->id = $id;
        $this->name = $name;
        $this->value = $value;
    }
}

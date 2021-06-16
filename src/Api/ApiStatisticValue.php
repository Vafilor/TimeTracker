<?php

declare(strict_types=1);

namespace App\Api;

use App\Entity\Statistic;
use App\Entity\StatisticValue;

class ApiStatisticValue
{
    public string $value;

    public static function fromEntity(StatisticValue $statisticValue): ApiStatisticValue
    {
        return new ApiStatisticValue($statisticValue->getValue());
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

    public function __construct(string $value)
    {
        $this->value = $value;
    }
}

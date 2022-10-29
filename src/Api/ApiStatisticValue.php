<?php

declare(strict_types=1);

namespace App\Api;

use App\Entity\StatisticValue;
use App\Entity\User;
use App\Util\DateFormatType;

class ApiStatisticValue
{
    public string $id;
    public float $value;
    public ApiStatistic $statistic;

    public static function fromEntity(StatisticValue $statisticValue, User $user, string $format = DateFormatType::DATE_TIME): ApiStatisticValue
    {
        return new ApiStatisticValue(
            ApiStatistic::fromEntity($statisticValue->getStatistic(), $user, $format),
            $statisticValue->getIdString(),
            $statisticValue->getValue()
        );
    }

    /**
     * @param StatisticValue[]|iterable $entities
     *
     * @return ApiStatisticValue[]
     */
    public static function fromEntities(iterable $entities, User $user, string $format = DateFormatType::DATE_TIME): array
    {
        $items = [];
        foreach ($entities as $entity) {
            $items[] = self::fromEntity($entity, $user, $format);
        }

        return $items;
    }

    public function __construct(ApiStatistic $statistic, string $id, float $value)
    {
        $this->statistic = $statistic;
        $this->id = $id;
        $this->value = $value;
    }
}

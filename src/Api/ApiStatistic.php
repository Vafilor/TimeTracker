<?php

declare(strict_types=1);

namespace App\Api;

use App\Entity\Statistic;
use App\Entity\User;
use App\Util\DateFormatType;

class ApiStatistic
{
    public string $name;
    public string $canonicalName;
    public string $createdAt;
    public int $createdAtEpoch;
    public string $color;
    public string $unit;
    public ?string $icon;
    public ?string $url;

    public static function fromEntity(Statistic $statistic, User $user, string $format = DateFormatType::DATE_TIME): ApiStatistic
    {
        $entity = new ApiStatistic($statistic->getName());
        $entity->canonicalName = $statistic->getCanonicalName();
        $entity->createdAt = ApiDateTime::formatUserDate($statistic->getCreatedAt(), $user, $format);
        $entity->createdAtEpoch = $statistic->getCreatedAt()->getTimestamp();
        $entity->color = $statistic->getColor();
        $entity->icon = $statistic->getIcon();
        $entity->unit = $statistic->getUnit();

        return $entity;
    }

    /**
     * @param Statistic[]|iterable $entities
     * @return ApiStatistic[]
     */
    public static function fromEntities(iterable $entities, User $user, string $format = DateFormatType::DATE_TIME): array
    {
        $items = [];
        foreach ($entities as $entity) {
            $items[] = self::fromEntity($entity, $user, $format);
        }

        return $items;
    }

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->url = null;
        $this->icon = null;
    }
}

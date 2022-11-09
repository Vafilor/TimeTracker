<?php

declare(strict_types=1);

namespace App\Transfer;

use App\Entity\Statistic;
use App\Entity\StatisticValue;
use App\Util\DateTimeUtil;

class TransferStatisticValue
{
    public string $id;

    public int $createdAt;

    public float $value;

    public int $startedAt;

    public ?int $endedAt = null;

    public string $statisticId;

    public ?string $timeEntryId = null;

    public ?string $timestampId = null;

    public static function fromEntity(StatisticValue $value): TransferStatisticValue
    {
        $transfer = new TransferStatisticValue();
        $transfer->id = $value->getIdString();
        $transfer->createdAt = $value->getCreatedAt()->getTimestamp();
        $transfer->value = $value->getValue();
        $transfer->startedAt = $value->getStartedAt()->getTimestamp();

        if ($value->hasEnded()) {
            $transfer->endedAt = $value->getEndedAt()->getTimestamp();
        }

        $transfer->statisticId = $value->getStatistic()->getIdString();

        if ($value->hasTimeEntry()) {
            $transfer->timeEntryId = $value->getTimeEntry()->getIdString();
        }

        if ($value->hasTimestamp()) {
            $transfer->timestampId = $value->getTimestamp()->getIdString();
        }

        return $transfer;
    }

    public static function fromEntities(iterable $entities): array
    {
        $items = [];
        foreach ($entities as $entity) {
            $items[] = self::fromEntity($entity);
        }

        return $items;
    }

    public function toEntity(Statistic $statistic): StatisticValue
    {
        $value = new StatisticValue($statistic, $this->value);

        $value->setCreatedAt(DateTimeUtil::dateFromTimestamp($this->createdAt));
        $value->setStartedAt(DateTimeUtil::dateFromTimestamp($this->startedAt));

        if ($this->endedAt) {
            $value->setEndedAt(DateTimeUtil::dateFromTimestamp($this->endedAt));
        }

        return $value;
    }
}

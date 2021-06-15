<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Entity\Statistic;
use App\Util\TimeType;
use InvalidArgumentException;

class StatisticEditModel
{
    private string $description;
    private string $valueType;
    private string $timeType;

    public static function fromEntity(Statistic $statistic): StatisticEditModel
    {
        return new StatisticEditModel(
            $statistic->getDescription(),
            $statistic->getValueType(),
            $statistic->getTimeType()
        );
    }

    public function __construct(string $description, string $valueType, string $timeType)
    {
        $this->setDescription($description);
        $this->setValueType($valueType);
        $this->setTimeType($timeType);
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function hasDescription(): bool
    {
        return !is_null($this->description);
    }

    public function setDescription(?string $description): self
    {
        if (is_null($description)) {
            $description = '';
        }

        $this->description = $description;
        return $this;
    }

    public function getValueType(): string
    {
        return $this->valueType;
    }

    public function setValueType(string $valueType): StatisticEditModel
    {
        $this->valueType = $valueType;
        return $this;
    }

    public function getTimeType(): string
    {
        return $this->timeType;
    }

    public function setTimeType(string $timeType): StatisticEditModel
    {
        if (!TimeType::isValid($timeType)) {
            throw new InvalidArgumentException(TimeType::invalidErrorMessage($timeType));
        }

        $this->timeType = $timeType;
        return $this;
    }
}

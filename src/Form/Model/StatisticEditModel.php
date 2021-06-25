<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Entity\Statistic;
use App\Util\TimeType;
use InvalidArgumentException;

class StatisticEditModel
{
    private ?string $name;
    private string $description;
    private string $timeType;

    public static function fromEntity(Statistic $statistic): StatisticEditModel
    {
        return new StatisticEditModel(
            $statistic->getDescription(),
            $statistic->getTimeType()
        );
    }

    public function __construct(string $description, string $timeType)
    {
        $this->name = null;
        $this->setDescription($description);
        $this->setTimeType($timeType);
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        if (is_null($description)) {
            $description = '';
        }

        $this->description = $description;
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function hasName(): bool
    {
        return !is_null($this->name);
    }

    public function setName(?string $name): StatisticEditModel
    {
        $this->name = $name;
        return $this;
    }
}

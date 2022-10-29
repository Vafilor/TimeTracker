<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Entity\Statistic;
use App\Util\TimeType;
use InvalidArgumentException;

class EditStatisticModel
{
    private string $name;
    private string $description;
    private string $timeType;
    // icon is optional
    private ?string $icon;
    private string $color;
    private string $unit;

    public static function fromEntity(Statistic $statistic): self
    {
        $model = new EditStatisticModel(
            $statistic->getName(),
            $statistic->getDescription(),
            $statistic->getTimeType(),
            $statistic->getColor(),
            $statistic->getIcon()
        );

        $model->setUnit($statistic->getUnit());

        return $model;
    }

    public function __construct(string $name, string $description, string $timeType, string $color, ?string $icon)
    {
        $this->name = $name;
        $this->setDescription($description);
        $this->setTimeType($timeType);
        $this->color = $color;
        $this->icon = $icon;
        $this->unit = '';
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

    public function setTimeType(string $timeType): self
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

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function setColor(string $color): self
    {
        $this->color = $color;

        return $this;
    }

    public function getUnit(): string
    {
        return $this->unit;
    }

    public function setUnit(?string $unit): self
    {
        if (is_null($unit)) {
            $unit = '';
        }

        $this->unit = $unit;

        return $this;
    }
}

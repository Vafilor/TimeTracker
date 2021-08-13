<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Util\TimeType;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

class StatisticModel
{
    /**
     * @Assert\NotBlank()
     */
    private string $name;
    private string $description;
    private string $timeType;

    public function __construct()
    {
        $this->name = '';
        $this->description = '';
        $this->timeType = 'instant';
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
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

    public function getTimeType(): string
    {
        return $this->timeType;
    }

    public function setTimeType(string $timeType): StatisticModel
    {
        if (!TimeType::isValid($timeType)) {
            throw new InvalidArgumentException(TimeType::invalidErrorMessage($timeType));
        }

        $this->timeType = $timeType;

        return $this;
    }
}

<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Entity\Statistic;
use DateTime;
use DateTimeZone;
use Symfony\Component\Validator\Constraints as Assert;

class AddStatisticValueModel
{
    /**
     * @Assert\NotBlank()
     */
    private ?string $statisticName;

    /**
     * @Assert\NotNull()
     * @Assert\GreaterThan(0)
     */
    private ?float $value;

    private ?DateTime $day;

    public function __construct(string $statisticName = '', float $value = null)
    {
        $this->statisticName = $statisticName;
        $this->value = $value;
        $this->day = null;
    }

    public function setStatisticName(?string $statisticName): self
    {
        $this->statisticName = $statisticName;
        return $this;
    }

    public function setValue(?float $value): self
    {
        $this->value = $value;
        return $this;
    }

    /**
     * Returns the statistic name in canonical form.
     *
     * @return string
     */
    public function getStatisticName(): string
    {
        return $this->statisticName;
    }

    public function getCanonicalStatisticName(): string
    {
        return Statistic::canonicalizeName($this->statisticName);
    }

    /**
     * @return float
     */
    public function getValue(): float
    {
        return $this->value;
    }

    public function getDay(): ?DateTime
    {
        return $this->day;
    }

    public function setDay(?DateTime $day): AddStatisticValueModel
    {
        if ($day) {
            $day->setTimezone(new DateTimeZone('UTC'));
        }

        $this->day = $day;
        return $this;
    }
}

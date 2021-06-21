<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Entity\Statistic;
use Symfony\Component\Validator\Constraints as Assert;

class AddStatisticValue
{
    /**
     * @Assert\NotBlank()
     * @var string|null
     */
    private ?string $statisticName;

    /**
     * @Assert\NotNull()
     */
    private ?float $value;

    public function __construct(string $statisticName = '', float $value = 0.0)
    {
        $this->statisticName = $statisticName;
        $this->value = $value;
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
        return Statistic::canonicalizeName($this->statisticName);
    }

    /**
     * @return float
     */
    public function getValue(): float
    {
        return $this->value;
    }
}

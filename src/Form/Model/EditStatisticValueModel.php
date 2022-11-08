<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Entity\StatisticValue;
use Symfony\Component\Validator\Constraints as Assert;

class EditStatisticValueModel
{
    #[Assert\NotNull(message: 'This value should not be blank.')]
    private ?float $value;

    public static function fromEntity(StatisticValue $statisticValue): self
    {
        return new EditStatisticValueModel($statisticValue->getValue());
    }

    public function __construct(float $value)
    {
        $this->value = $value;
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function setValue(?float $value): self
    {
        $this->value = $value;

        return $this;
    }
}

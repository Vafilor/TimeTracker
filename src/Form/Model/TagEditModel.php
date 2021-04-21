<?php

declare(strict_types=1);

namespace App\Form\Model;

class TagEditModel
{
    private string $color;

    public function getColor(): string
    {
        return $this->color;
    }

    public function setColor(string $color): self
    {
        $this->color = $color;
        return $this;
    }
}

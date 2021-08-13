<?php

declare(strict_types=1);

namespace App\Form\Model;

class TagListFilterModel
{
    private ?string $name;

    public function __construct(?string $name = null)
    {
        $this->setName($name);
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }
}

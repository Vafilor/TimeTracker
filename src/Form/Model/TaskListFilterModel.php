<?php

declare(strict_types=1);

namespace App\Form\Model;

class TaskListFilterModel
{
    private bool $showCompleted;
    private ?string $name;
    private ?string $description;

    public function __construct()
    {
        $this->showCompleted = false;
        $this->name = null;
        $this->description = null;
    }

    public function getShowCompleted(): bool
    {
        return $this->showCompleted;
    }

    public function setShowCompleted(bool $showCompleted): self
    {
        $this->showCompleted = $showCompleted;
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

    public function setName(?string $name): self
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
        $this->description = $description;
        return $this;
    }
}

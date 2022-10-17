<?php

declare(strict_types=1);

namespace App\Form\Model;

class ActionTaskModel {
    private ?string $taskId;
    private ?string $action;
    private ?string $value;

    public function __construct()
    {
        $this->taskId = null;
        $this->action = null;
        $this->value = null;
    }

    public function getTaskId(): ?string
    {
        return $this->taskId;
    }

    public function setTaskId(?string $taskId): self
    {
        $this->taskId = $taskId;
        return $this;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(?string $action): self
    {
        $this->action = $action;
        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): self
    {
        $this->value = $value;
        return $this;
    }
}
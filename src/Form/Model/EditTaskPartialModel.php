<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Entity\Task;
use DateTime;

class EditTaskPartialModel
{
    private string $name;

    private int $priority;

    private ?DateTime $completedAt;

    private ?DateTime $dueAt;

    private ?int $timeEstimate;

    private bool $active;

    public static function fromEntity(Task $task): self
    {
        $model = new EditTaskPartialModel();
        $model->setName($task->getName());
        $model->setPriority($task->getPriority());
        $model->setCompletedAt($task->getCompletedAt());
        $model->setDueAt($task->getDueAt());
        $model->setTimeEstimate($task->getTimeEstimate());
        $model->setActive($task->isActive());

        return $model;
    }

    public function __construct()
    {
        $this->name = '';
        $this->completedAt = null;
        $this->dueAt = null;
        $this->timeEstimate = null;
        $this->priority = 0;
        $this->active = false;
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

    public function getCompletedAt(): ?DateTime
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?DateTime $completedAt): self
    {
        $this->completedAt = $completedAt;

        return $this;
    }

    public function getDueAt(): ?DateTime
    {
        return $this->dueAt;
    }

    public function setDueAt(?DateTime $dueAt): self
    {
        $this->dueAt = $dueAt;

        return $this;
    }

    public function getTimeEstimate(): ?int
    {
        return $this->timeEstimate;
    }

    public function setTimeEstimate(?int $timeEstimate): self
    {
        $this->timeEstimate = $timeEstimate;

        return $this;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): self
    {
        $this->priority = $priority;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }
}

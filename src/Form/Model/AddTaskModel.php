<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Entity\Task;
use DateTime;

class AddTaskModel
{
    private string $name;
    private ?string $description;
    private ?DateTime $dueAt;
    private ?string $parentTask;
    private ?string $taskTemplate;
    private bool $active;

    public static function fromEntity(Task $task): self
    {
        $model = new AddTaskModel();
        $model->setName($task->getName());
        $model->setDescription($task->getDescription());
        $model->setDueAt($task->getDueAt());
        $model->active = $task->isActive();

        if ($task->hasParent()) {
            $model->setParentTask($task->getParent()->getIdString());
        }

        return $model;
    }

    public function __construct()
    {
        $this->name = '';
        $this->description = '';
        $this->parentTask = null;
        $this->dueAt = null;
        $this->taskTemplate = null;
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

    public function getDescription(): string
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

    public function getParentTask(): ?string
    {
        return $this->parentTask;
    }

    public function hasParentTask(): bool
    {
        return !is_null($this->parentTask);
    }

    public function setParentTask(?string $parentTask): self
    {
        $this->parentTask = $parentTask;

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

    public function getTaskTemplate(): ?string
    {
        return $this->taskTemplate;
    }

    public function hasTaskTemplate(): bool
    {
        return !is_null($this->taskTemplate);
    }

    public function setTaskTemplate(?string $taskTemplate): self
    {
        $this->taskTemplate = $taskTemplate;

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

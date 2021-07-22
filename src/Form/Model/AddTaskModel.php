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

    public static function fromEntity(Task $task): self
    {
        $model = new AddTaskModel();
        $model->setName($task->getName());
        $model->setDescription($task->getDescription());
        $model->setDueAt($task->getDueAt());

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
}

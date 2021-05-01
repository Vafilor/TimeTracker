<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Entity\Task;
use DateTime;

class TaskModel
{
    private string $name;
    private string $description;
    private ?DateTime $completedAt;

    public static function fromEntity(Task $task): TaskModel
    {
        $model = new TaskModel();
        $model->setName($task->getName());
        $model->setDescription($task->getDescription());
        $model->setCompletedAt($task->getCompletedAt());

        return $model;
    }

    public function __construct()
    {
        $this->name = '';
        $this->description = '';
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

    public function setDescription(string $description): self
    {
        $this->description = $description;
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
}

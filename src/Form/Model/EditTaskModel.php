<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Entity\Task;
use DateInterval;
use DateTime;

class EditTaskModel
{
    private string $name;
    private ?string $description;
    private int $priority;
    private ?DateTime $completedAt;
    private ?DateTime $dueAt;
    private ?int $timeEstimate;
    private ?string $parentTask;
    private bool $template;

    public static function fromEntity(Task $task): self
    {
        $model = new EditTaskModel();
        $model->setName($task->getName());
        $model->setDescription($task->getDescription());
        $model->setCompletedAt($task->getCompletedAt());
        $model->setDueAt($task->getDueAt());
        $model->setTemplate($task->isTemplate());
        $model->timeEstimate = $task->getTimeEstimate();
        $model->priority = $task->getPriority();

        if ($task->hasParent()) {
            $model->setParentTask($task->getParent()->getIdString());
        }

        return $model;
    }

    public function __construct()
    {
        $this->name = '';
        $this->description = '';
        $this->completedAt = null;
        $this->parentTask = null;
        $this->dueAt = null;
        $this->template = false;
        $this->timeEstimate = null;
        $this->priority = 0;
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

    public function getCompletedAt(): ?DateTime
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?DateTime $completedAt): self
    {
        $this->completedAt = $completedAt;
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

    public function isTemplate(): bool
    {
        return $this->template;
    }

    public function setTemplate(bool $template): self
    {
        $this->template = $template;
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
}

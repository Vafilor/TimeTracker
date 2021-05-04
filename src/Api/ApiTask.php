<?php

declare(strict_types=1);

namespace App\Api;

use App\Entity\Task;
use App\Entity\User;

class ApiTask
{
    public string $id;
    public string $name;
    public string $description;
    public ?string $completedAt;

    public static function fromEntity(Task $task,  User $user, string $format = 'date'): ApiTask
    {
        $apiTask = new ApiTask($task->getIdString(), $task->getName());
        $apiTask->setDescription($task->getDescription());
        if ($task->completed()) {
            $completedAtString = ApiDateTime::formatUserDate($task->getCompletedAt(), $user, $format);
            $apiTask->setCompletedAt($completedAtString);
        }

        return $apiTask;
    }

    public function __construct(string $id, string $name) {
        $this->id = $id;
        $this->name = $name;
        $this->description = '';
        $this->completedAt = null;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
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

    public function getCompletedAt(): ?string
    {
        return $this->completedAt;
    }

    public function setCompletedAt(string $completedAt): self
    {
        $this->completedAt = $completedAt;
        return $this;
    }
}
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
    public string $createdAt;
    public ?string $completedAt = null;
    public ?string $url = null;

    public static function fromEntity(Task $task, User $user, string $format = 'date'): ApiTask
    {
        $apiTask = new ApiTask($task->getIdString(), $task->getName());
        $apiTask->createdAt = ApiDateTime::formatUserDate($task->getCreatedAt(), $user, $format);
        $apiTask->setDescription($task->getDescription());
        if ($task->completed()) {
            $completedAtString = ApiDateTime::formatUserDate($task->getCompletedAt(), $user, $format);
            $apiTask->setCompletedAt($completedAtString);
        }

        return $apiTask;
    }

    /**
     * @param Task[] $entities
     * @param User $user
     * @param string $format
     * @return ApiTask[]
     */
    public static function fromEntities(iterable $entities, User $user, string $format = 'date'): array
    {
        $items = [];
        foreach ($entities as $entity) {
            $items[] = self::fromEntity($entity, $user, $format);
        }

        return $items;
    }

    public function __construct(string $id, string $name)
    {
        $this->id = $id;
        $this->name = $name;
        $this->description = '';
        $this->completedAt = null;
        $this->url = null;
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

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): ApiTask
    {
        $this->url = $url;
        return $this;
    }
}

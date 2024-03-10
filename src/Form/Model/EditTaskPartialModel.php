<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Entity\Task;
use DateTime;

class EditTaskPartialModel
{
    private string $name;

    private string $description;

    private int $priority;

    private ?DateTime $completedAt;

    private ?DateTime $dueAt;

    private ?int $timeEstimate;

    private bool $active;

    private ?string $tags;

    public static function fromEntity(Task $task): self
    {
        $model = new EditTaskPartialModel();
        $model->setName($task->getName());
        $model->setDescription($task->getDescription());
        $model->setPriority($task->getPriority());
        $model->setCompletedAt($task->getCompletedAt());
        $model->setDueAt($task->getDueAt());
        $model->setTimeEstimate($task->getTimeEstimate());
        $model->setActive($task->isActive());
        $model->setTags($task->getTagNames());

        return $model;
    }

    public function __construct()
    {
        $this->name = '';
        $this->completedAt = null;
        $this->description = '';
        $this->dueAt = null;
        $this->timeEstimate = null;
        $this->priority = 0;
        $this->active = false;
        $this->tags = null;
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
        $this->description = $description ?? '';
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

    public function getTags(): ?string
    {
        return $this->tags;
    }

    /**
     * @return array|string[]
     */
    public function getTagsArray(): array
    {
        if (is_null($this->tags) || '' === $this->tags) {
            return [];
        }

        $results = explode(',', $this->tags);

        $results = array_map(
            fn ($tagRaw) => [
                'name' => str_replace(' ', '', $tagRaw),
                'color' => '#5d5d5d',
            ],
            $results
        );

        return $results;
    }

    public function setTags(?string $tags): self
    {
        $this->tags = $tags;

        return $this;
    }
}

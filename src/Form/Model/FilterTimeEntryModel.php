<?php

declare(strict_types=1);

namespace App\Form\Model;

use DateTime;

class FilterTimeEntryModel
{
    private ?DateTime $start;
    private ?DateTime $end;
    private ?string $tags;
    private ?string $taskId;

    public function __construct()
    {
        $this->start = null;
        $this->end = null;
        $this->tags = null;
        $this->taskId = null;
    }

    public function getStart(): ?DateTime
    {
        return $this->start;
    }

    public function hasStart(): bool
    {
        return !is_null($this->start);
    }

    public function setStart(?DateTime $start): self
    {
        $this->start = $start;

        return $this;
    }

    public function getEnd(): ?DateTime
    {
        return $this->end;
    }

    public function hasEnd(): bool
    {
        return !is_null($this->end);
    }

    public function setEnd(?DateTime $end): self
    {
        $this->end = $end;

        return $this;
    }

    public function getTags(): string
    {
        return $this->tags;
    }

    public function hasTags(): bool
    {
        return !is_null($this->tags) && '' !== $this->tags;
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
            fn ($tagRaw) => str_replace(' ', '', $tagRaw),
            $results
        );

        return $results;
    }

    public function setTags(?string $tags): self
    {
        $this->tags = $tags;

        return $this;
    }

    public function getTaskId(): ?string
    {
        return $this->taskId;
    }

    public function hasTask(): bool
    {
        return !is_null($this->taskId);
    }

    public function setTaskId(?string $taskId): FilterTimeEntryModel
    {
        $this->taskId = $taskId;

        return $this;
    }
}

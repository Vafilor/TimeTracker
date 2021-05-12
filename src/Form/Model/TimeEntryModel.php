<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Entity\Task;
use App\Entity\TimeEntry;
use DateTime;

class TimeEntryModel
{
    private ?DateTime $startedAt;
    private ?DateTime $endedAt;
    private ?string $description;

    public static function fromEntity(TimeEntry $timeEntry): TimeEntryModel
    {
        $model = new TimeEntryModel();
        $model->setStartedAt($timeEntry->getStartedAt());
        $model->setEndedAt($timeEntry->getEndedAt());
        $model->setDescription($timeEntry->getDescription());

        return $model;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function hasDescription(): bool
    {
        return !is_null($this->description);
    }

    public function setDescription(?string $description): self
    {
        if (is_null($description)) {
            $description = '';
        }

        $this->description = $description;
        return $this;
    }

    public function getStartedAt(): ?DateTime
    {
        return $this->startedAt;
    }

    public function hasStartedAt(): bool
    {
        return !is_null($this->startedAt);
    }

    public function setStartedAt(?DateTime $startedAt): self
    {
        $this->startedAt = $startedAt;
        return $this;
    }

    public function getEndedAt(): ?DateTime
    {
        return $this->endedAt;
    }

    public function hasEndedAt(): bool
    {
        return !is_null($this->endedAt);
    }

    public function setEndedAt(?DateTime $endedAt): self
    {
        $this->endedAt = $endedAt;
        return $this;
    }

    public function isEnded(): bool
    {
        return !is_null($this->endedAt);
    }
}

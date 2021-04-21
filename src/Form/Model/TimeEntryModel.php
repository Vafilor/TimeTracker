<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Entity\TimeEntry;
use DateTime;

class TimeEntryModel
{
    private DateTime $createdAt;
    private ?DateTime $endedAt;
    private string $description;

    public static function fromEntity(TimeEntry $timeEntry): TimeEntryModel
    {
        $model = new TimeEntryModel();
        $model->setCreatedAt($timeEntry->getCreatedAt());
        $model->setEndedAt($timeEntry->getEndedAt());
        $model->setDescription($timeEntry->getDescription());

        return $model;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return TimeEntryModel
     */
    public function setDescription(?string $description): self
    {
        if (is_null($description)) {
            $description = '';
        }

        $this->description = $description;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param DateTime $createdAt
     * @return TimeEntryModel
     */
    public function setCreatedAt(DateTime $createdAt): TimeEntryModel
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getEndedAt(): ?DateTime
    {
        return $this->endedAt;
    }

    /**
     * @param DateTime|null $endedAt
     * @return TimeEntryModel
     */
    public function setEndedAt(?DateTime $endedAt): TimeEntryModel
    {
        $this->endedAt = $endedAt;
        return $this;
    }

    public function isEnded(): bool
    {
        return !is_null($this->endedAt);
    }
}

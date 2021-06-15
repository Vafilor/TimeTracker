<?php

namespace App\Entity;

use App\Repository\StatisticValueRepository;
use App\Traits\CreateTimestampableTrait;
use App\Traits\UUIDTrait;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=StatisticValueRepository::class)
 */
class StatisticValue
{
    use UUIDTrait;
    use CreateTimestampableTrait;

    /**
     * @ORM\ManyToOne(targetEntity=Statistic::class)
     * @ORM\JoinColumn(nullable=false)
     * @var Statistic
     */
    private $statistic;

    /**
     * @ORM\Column(type="string", length=255)
     * @var string
     */
    private $value;

    /**
     * @ORM\Column(type="datetimetz")
     * @var DateTime
     */
    protected $startedAt;

    /**
     * @ORM\Column(type="datetimetz", nullable=true)
     * @var DateTime|null
     */
    protected $endedAt;

    /**
     * @ORM\ManyToOne(targetEntity=TimeEntry::class)
     * @var TimeEntry|null
     */
    private $timeEntry;

    /**
     * @ORM\ManyToOne(targetEntity=Timestamp::class)
     * @var Timestamp|null
     */
    private $timestamp;

    public function getStatistic(): ?Statistic
    {
        return $this->statistic;
    }

    public function setStatistic(?Statistic $statistic): self
    {
        $this->statistic = $statistic;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getStartedAt(): DateTime
    {
        return $this->startedAt;
    }

    public function setStartedAt(DateTime $startedAt): self
    {
        $this->startedAt = $startedAt;
        return $this;
    }

    public function getEndedAt(): ?DateTime
    {
        return $this->endedAt;
    }

    public function setEndedAt(DateTime $endedAt): static
    {
        $endedAt->setTimezone(new DateTimeZone('UTC'));
        $this->endedAt = $endedAt;
        return $this;
    }

    public function getTimeEntry(): ?TimeEntry
    {
        return $this->timeEntry;
    }

    public function setTimeEntry(?TimeEntry $timeEntry): self
    {
        $this->timeEntry = $timeEntry;

        return $this;
    }

    public function getTimestamp(): ?Timestamp
    {
        return $this->timestamp;
    }

    public function setTimestamp(?Timestamp $timestamp): self
    {
        $this->timestamp = $timestamp;

        return $this;
    }
}

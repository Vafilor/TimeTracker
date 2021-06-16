<?php

namespace App\Entity;

use App\Repository\StatisticValueRepository;
use App\Traits\CreateTimestampableTrait;
use App\Traits\UUIDTrait;
use App\Util\TimeType;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

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
     */
    private Statistic $statistic;

    /**
     * @ORM\Column(type="float")
     */
    private float $value;

    /**
     * @ORM\Column(type="datetimetz")
     */
    protected DateTime $startedAt;

    /**
     * @ORM\Column(type="datetimetz", nullable=true)
     */
    protected ?DateTime $endedAt;

    /**
     * @ORM\ManyToOne(targetEntity=TimeEntry::class)
     */
    private ?TimeEntry $timeEntry;

    /**
     * @ORM\ManyToOne(targetEntity=Timestamp::class, inversedBy="statisticValues")
     */
    private ?Timestamp $timestamp;

    public static function fromTimestamp(Statistic $statistic, float $value, Timestamp $timestamp): StatisticValue
    {
        if ($statistic->getTimeType() !== TimeType::instant) {
            throw new InvalidArgumentException("Statistic is not an 'instant' type. Unable to associate to timestamp");
        }

        $value = new StatisticValue($statistic, $value);

        $value->setStartedAt($timestamp->getCreatedAt());
        $value->setEndedAt($timestamp->getCreatedAt());
        $value->setTimestamp($timestamp);

        return $value;
    }

    public function __construct(Statistic $statistic, float $value)
    {
        $this->id = Uuid::uuid4();
        $this->markCreated();
        $this->statistic = $statistic;
        $this->value = $value;
    }

    public function getStatistic(): Statistic
    {
        return $this->statistic;
    }

    public function setStatistic(Statistic $statistic): self
    {
        $this->statistic = $statistic;

        return $this;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(float $value): self
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

    public function hasResource(): bool
    {
        return !is_null($this->timestamp) || !is_null($this->timeEntry);
    }
}

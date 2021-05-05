<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TimeEntryRepository;
use App\Traits\UpdateTimestampableTrait;
use App\Traits\UUIDTrait;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

/**
 * @ORM\Entity(repositoryClass=TimeEntryRepository::class)
 * @ORM\HasLifecycleCallbacks()
*/
class TimeEntry
{
    use UUIDTrait;
    use UpdateTimestampableTrait;

    /**
     * @ORM\Column(type="datetimetz")
     * @var DateTime
     */
    protected $createdAt;

    /**
     * @ORM\Column(type="datetimetz")
     * @var DateTime
     */
    protected $startedAt;

    /**
     * @ORM\Column(type="datetimetz", nullable=true)
     * @var DateTime
     */
    protected $endedAt;

    /**
     * @ORM\Column(type="datetimetz", nullable=true)
     * @var DateTime
     */
    protected $deletedAt;

    /**
     * @var string
     * @ORM\Column(type="text")
     */
    private $description;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="timeEntries")
     * @ORM\JoinColumn(nullable=false)
     */
    private $owner;

    /**
     * @ORM\OneToMany(targetEntity=TimeEntryTag::class, mappedBy="timeEntry")
     */
    private $timeEntryTags;

    /**
     * @ORM\ManyToOne(targetEntity=Task::class, inversedBy="timeEntries")
     */
    private $task;

    public function __construct(User $owner, DateTimeInterface $createdAt = null)
    {
        $this->id = Uuid::uuid4();
        $this->owner = $owner;
        $this->description = '';
        $this->timeEntryTags = new ArrayCollection();

        if (is_null($createdAt)) {
            $createdAt = new DateTime('now', new DateTimeZone('UTC'));
        }

        $this->createdAt = $createdAt;
        $this->startedAt = $createdAt;
        $this->updatedAt = $createdAt;
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

    public function setCreatedAt(DateTime $createdAt): self
    {
        $createdAt->setTimezone(new DateTimeZone('UTC'));
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
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

    public function getUpdatedAt(): DateTimeImmutable|DateTime
    {
        return $this->updatedAt;
    }

    public function duration(): DateInterval
    {
        if (!$this->isOver()) {
            $now = new DateTime('now', new DateTimeZone('UTC'));
            return $now->diff($this->createdAt);
        }

        return $this->endedAt->diff($this->createdAt);
    }

    public function stop(DateTimeInterface $endedAt = null): self
    {
        if (is_null($endedAt)) {
            $endedAt = new DateTime('now', new DateTimeZone('UTC'));;
        }

        if ($endedAt < $this->createdAt) {
            throw new InvalidArgumentException("End time can not be before start time");
        }

        $this->endedAt = $endedAt;

        return $this;
    }

    public function resume(): self
    {
        $this->endedAt = null;

        return $this;
    }

    public function isOver(): bool {
        return !is_null($this->endedAt);
    }

    public function running(): bool {
        return !$this->isOver();
    }

    public function getOwner(): User
    {
        return $this->owner;
    }

    public function setOwner(User $owner): self
    {
        $this->owner = $owner;

        return $this;
    }

    public function softDelete(DateTimeInterface $when = null): self
    {
        if (is_null($when)) {
            $when = new DateTimeImmutable('now', new DateTimeZone('UTC'));;
        }

        $this->deletedAt = $when;

        return $this;
    }

    /**
     * @return Collection|TimeEntryTag[]
     */
    public function getTimeEntryTags(): Collection
    {
        return $this->timeEntryTags;
    }

    public function setEndedAt(DateTime $endedAt): static
    {
        $endedAt->setTimezone(new DateTimeZone('UTC'));
        $this->endedAt = $endedAt;
        return $this;
    }

    public function getTask(): ?Task
    {
        return $this->task;
    }

    public function assignedToTask(): bool
    {
        return !is_null($this->task);
    }

    public function setTask(?Task $task): self
    {
        $this->task = $task;

        return $this;
    }

    public function removeTask(): self
    {
        $this->task = null;

        return $this;
    }
}

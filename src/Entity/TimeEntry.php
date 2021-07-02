<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TimeEntryRepository;
use App\Traits\AssignableToUserTrait;
use App\Traits\CreateTimestampableTrait;
use App\Traits\TaggableTrait;
use App\Traits\UpdateTimestampableTrait;
use App\Traits\UUIDTrait;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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
    use CreateTimestampableTrait;
    use UpdateTimestampableTrait;
    use TaggableTrait;
    use AssignableToUserTrait;

    /**
     * @ORM\Column(type="datetime")
     */
    protected DateTime $startedAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected ?DateTime $endedAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected ?DateTime $deletedAt;

    /**
     * @ORM\Column(type="text")
     */
    private string $description;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="timeEntries")
     * @ORM\JoinColumn(nullable=false)
     */
    private User $assignedTo;

    /**
     * @ORM\OneToMany(targetEntity=TagLink::class, mappedBy="timeEntry")
     * @var TagLink[]|Collection
     */
    private Collection $tagLinks;

    /**
     * @ORM\OneToMany(targetEntity=StatisticValue::class, mappedBy="timeEntry")
     * @var StatisticValue[]|Collection
     */
    private Collection $statisticValues;

    /**
     * @ORM\ManyToOne(targetEntity=Task::class, inversedBy="timeEntries")
     */
    private ?Task $task;

    public function __construct(User $assignedTo, DateTime $createdAt = null)
    {
        $this->id = Uuid::uuid4();
        $this->markCreated($createdAt);
        $this->assignTo($assignedTo);
        $this->description = '';
        $this->tagLinks = new ArrayCollection();
        $this->startedAt = $this->createdAt;
        $this->updatedAt = $this->createdAt;
        $this->task = null;
        $this->endedAt = null;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function isDescriptionEmpty(): bool
    {
        return $this->description === '';
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

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

    public function getUpdatedAt(): DateTimeImmutable|DateTime
    {
        return $this->updatedAt;
    }

    public function duration(): DateInterval
    {
        if (!$this->isOver()) {
            $now = new DateTime('now', new DateTimeZone('UTC'));
            return $now->diff($this->startedAt);
        }

        return $this->endedAt->diff($this->startedAt);
    }

    public function stop(DateTime $endedAt = null): self
    {
        if (is_null($endedAt)) {
            $endedAt = new DateTime('now', new DateTimeZone('UTC'));
            ;
        }

        if ($endedAt < $this->createdAt) {
            throw new InvalidArgumentException("End time can not be before start time");
        }

        $this->endedAt = $endedAt;

        foreach ($this->getStatisticValues() as $statisticValue) {
            $statisticValue->setEndedAt($endedAt);
        }

        return $this;
    }

    public function resume(): self
    {
        $this->endedAt = null;

        return $this;
    }

    public function isOver(): bool
    {
        return !is_null($this->endedAt);
    }

    public function running(): bool
    {
        return !$this->isOver();
    }

    public function getDeletedAt(): ?DateTime
    {
        return $this->deletedAt;
    }

    public function isDeleted(): bool
    {
        return !is_null($this->deletedAt);
    }

    public function softDelete(DateTime $when = null): self
    {
        if (is_null($when)) {
            $when = new DateTime('now', new DateTimeZone('UTC'));
        }

        $this->deletedAt = $when;

        return $this;
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

    /**
     * Add a TagLink to this TimeEntry. This does not add it to the database,
     * it is purely for this object in memory.
     * To persist the TagLink it must be persisted outside of this method.
     *
     * @param TagLink $tagLink
     * @return $this
     */
    public function addTagLink(TagLink $tagLink): self
    {
        $this->tagLinks->add($tagLink);

        return $this;
    }

    /**
     * @return StatisticValue[]|Collection
     */
    public function getStatisticValues(): Collection|array
    {
        return $this->statisticValues;
    }
}

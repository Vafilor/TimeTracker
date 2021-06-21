<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TaskRepository;
use App\Traits\AssignableToUserTrait;
use App\Traits\CreateTimestampableTrait;
use App\Traits\TaggableTrait;
use App\Traits\UpdateTimestampableTrait;
use App\Traits\UUIDTrait;
use DateTime;
use DateTimeZone;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

/**
 * @ORM\Entity(repositoryClass=TaskRepository::class)
 */
class Task
{
    use UUIDTrait;
    use CreateTimestampableTrait;
    use UpdateTimestampableTrait;
    use TaggableTrait;
    use AssignableToUserTrait;

    /**
     * @ORM\Column(type="datetimetz", nullable=true)
     */
    private ?DateTime $completedAt;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $name;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $canonicalName;

    /**
     * @ORM\Column(type="text")
     */
    private string $description;

    /**
     * @ORM\Column(type="integer")
     */
    private int $priority;

    /**
     * @ORM\OneToMany(targetEntity=TimeEntry::class, mappedBy="task")
     * @var TimeEntry[]|Collection
     */
    private Collection $timeEntries;

    /**
     * @ORM\OneToMany(targetEntity=TagLink::class, mappedBy="task")
     * @var TagLink[]|Collection
     */
    private Collection $tagLinks;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="tasks")
     * @ORM\JoinColumn(nullable=false)
     */
    private User $assignedTo;

    public function __construct(User $assignedTo, string $name)
    {
        $this->id = Uuid::uuid4();
        $this->markCreated();
        $this->assignTo($assignedTo);
        $this->setName($name);
        $this->updatedAt = $this->createdAt;
        $this->timeEntries = new ArrayCollection();
        $this->description = '';
        $this->completedAt = null;
        $this->priority = 0;
        $this->tagLinks = new ArrayCollection();
    }

    private function canonicalizeName(string $name): string
    {
        return trim(strtolower($name));
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCanonicalName(): string
    {
        return $this->canonicalName;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        $this->canonicalName = $this->canonicalizeName($name);

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

    public function getTimeEntries(): Collection
    {
        return $this->timeEntries;
    }

    public function getCompletedAt(): ?DateTime
    {
        return $this->completedAt;
    }

    public function completed(): bool
    {
        return !is_null($this->completedAt);
    }

    public function complete(?DateTime $completedAt = null): self
    {
        if (is_null($completedAt)) {
            $completedAt = new DateTime('now', new DateTimeZone('UTC'));
        }

        $this->completedAt = $completedAt;

        return $this;
    }

    public function clearCompleted(): self
    {
        $this->completedAt = null;

        return $this;
    }

    public function setCompletedAt(?DateTime $completedAt): self
    {
        $this->completedAt = $completedAt;

        return $this;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): Task
    {
        $this->priority = $priority;
        return $this;
    }
}

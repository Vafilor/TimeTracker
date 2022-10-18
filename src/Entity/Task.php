<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TaskRepository;
use App\Traits\AssignableToUserTrait;
use App\Traits\CreateTimestampableTrait;
use App\Traits\SoftDeletableTrait;
use App\Traits\TaggableTrait;
use App\Traits\UpdateTimestampableTrait;
use App\Traits\UUIDTrait;
use App\Util\DateTimeUtil;
use DateTime;
use DateTimeZone;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

#[ORM\Entity(repositoryClass: TaskRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Task
{
    use UUIDTrait;
    use CreateTimestampableTrait;
    use UpdateTimestampableTrait;
    use SoftDeletableTrait;
    use TaggableTrait;
    use AssignableToUserTrait;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?DateTime $completedAt;

    #[ORM\Column(type: "string", length: 255)]
    private string $name;

    #[ORM\Column(type: "string", length: 255)]
    private string $canonicalName;

    #[ORM\Column(type: "text")]
    private string $description;

    #[ORM\Column(type: "integer")]
    private int $priority;

    /**
     * How long the task is estimated to take in seconds.
     */
    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $timeEstimate;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?DateTime $dueAt;

    #[ORM\Column(type: "boolean", nullable: false)]
    private bool $template;

    /**
     * If true, the task is currently being worked on, or, it's a target to work on.
     */
    #[ORM\Column(type: "boolean", nullable: false)]
    private bool $active;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?DateTime $closedAt;

    /**
     * @var TimeEntry[]|Collection
     */
    #[ORM\OneToMany(mappedBy: "task", targetEntity: TimeEntry::class)]
    private Collection $timeEntries;

    /**
     * @var TagLink[]|Collection
     */
    #[ORM\OneToMany(mappedBy: "task", targetEntity: TagLink::class)]
    private Collection $tagLinks;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: "tasks")]
    #[ORM\JoinColumn(nullable: false)]
    private User $assignedTo;

    #[ORM\ManyToOne(targetEntity: Task::class, inversedBy: "tasks")]
    private ?Task $parent;

    /**
     * @var Collection|Task[]
     */
    #[ORM\OneToMany(mappedBy: "parent", targetEntity: Task::class)]
    private Collection $tasks;

    public static function canonicalizeName(string $name): string
    {
        return trim(strtolower($name));
    }

    public function __construct(User $assignedTo, string $name)
    {
        $this->id = Uuid::uuid4();
        $this->markCreated();
        $this->assignTo($assignedTo);
        $this->setName($name);
        $this->updatedAt = $this->createdAt;
        $this->description = '';
        $this->completedAt = null;
        $this->closedAt = null;
        $this->dueAt = null;
        $this->priority = 0;
        $this->parent = null;
        $this->template = false;
        $this->timeEstimate = null;
        $this->active = false;
        $this->timeEntries = new ArrayCollection();
        $this->tagLinks = new ArrayCollection();
        $this->tasks = new ArrayCollection();
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

        $this->canonicalName = self::canonicalizeName($name);

        if (strlen($this->canonicalName) === 0) {
            throw new InvalidArgumentException('Name can not be blank once whitespace is removed.');
        }

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

    public function getDueAt(): ?DateTime
    {
        if (is_null($this->dueAt)) {
            return null;
        }

        return clone $this->dueAt;
    }

    public function setDueAt(?DateTime $dueAt): self
    {
        $this->dueAt = $dueAt;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function hasParent(): bool
    {
        return !is_null($this->parent);
    }

    public function setParent(?self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    public function removeParent(): self
    {
        $this->parent = null;

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

    /**
     * @return Collection|self[]
     */
    public function getSubtasks(): Collection
    {
        return $this->tasks->filter(fn (Task $task) => !$task->isDeleted());
    }

    /**
     * The lineage of the task, starting with the upper-most. So,
     * [Grandchild Task, Parent Task, Task]
     *
     * @return Task[]
     */
    public function getLineage(): array
    {
        $lineage = [$this];

        $element = $this;
        while ($element->hasParent()) {
            array_unshift($lineage, $element->getParent());
            $element = $element->getParent();
        }

        return $lineage;
    }

    public function isTemplate(): bool
    {
        return $this->template;
    }

    public function setTemplate(bool $template): self
    {
        $this->template = $template;

        return $this;
    }

    public function getTimeEstimate(): ?int
    {
        return $this->timeEstimate;
    }

    public function getTimeEstimateFormatted(): ?string
    {
        if (null === $this->timeEstimate) {
            return null;
        }

        $interval = DateTimeUtil::dateIntervalFromSeconds($this->timeEstimate);
        $format = '';

        if ($interval->h > 0) {
            $format = '%hh';
        }

        $format .= " %im %ss";

        return $interval->format($format);
    }

    public function setTimeEstimate(?int $timeEstimate): self
    {
        $this->timeEstimate = $timeEstimate;
        return $this;
    }

    public function getClosedAt(): ?DateTime
    {
        return $this->closedAt;
    }

    public function setClosedAt(?DateTime $closedAt): self
    {
        $this->closedAt = $closedAt;
        return $this;
    }
}

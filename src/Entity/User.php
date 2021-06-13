<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use App\Traits\CreateTimestampableTrait;
use App\Traits\UUIDTrait;
use DateTime;
use DateTimeZone;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @ORM\Table(name="users")
 */
class User extends BaseUser
{
    use UUIDTrait;
    use CreateTimestampableTrait;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    private $timezone;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    private $dateFormat;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    private $todayDateFormat;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    private $durationFormat;

    /**
     * @ORM\OneToMany(targetEntity=Task::class, mappedBy="createdBy", orphanRemoval=true)
     */
    private $tasks;

    /**
     * @ORM\OneToMany(targetEntity=TimeEntry::class, mappedBy="owner")
     */
    private $timeEntries;

    public function __construct(DateTime $createdAt = null)
    {
        parent::__construct();

        $this->timeEntries = new ArrayCollection();
        $this->timezone = "America/Los_Angeles";
        $this->dateFormat = 'm/d/Y h:i:s A';
        $this->todayDateFormat = 'h:i:s A';
        $this->durationFormat = '%hh %Im %Ss';
        $this->tasks = new ArrayCollection();

        $this->markCreated($createdAt);
    }

    public function gravatarUrl(int $size = 30): string
    {
        $baseUrl = 'https://www.gravatar.com/avatar/';
        $md5 = md5(strtolower(trim($this->getEmail())));
        $sizeQuery = '?s='.$size;

        return $baseUrl.$md5.$sizeQuery.'&d=mp';
    }

    /**
     * @return Collection|TimeEntry[]
     */
    public function getTimeEntries(): Collection
    {
        return $this->timeEntries;
    }

    public function getTimezone(): string
    {
        return $this->timezone;
    }

    public function setTimezone(string $timezone): self
    {
        $this->timezone = $timezone;
        return $this;
    }

    public function getDateFormat(): string
    {
        return $this->dateFormat;
    }

    public function setDateFormat(string $dateFormat): User
    {
        $this->dateFormat = $dateFormat;
        return $this;
    }

    public function getTodayDateFormat(): string
    {
        return $this->todayDateFormat;
    }

    public function setTodayDateFormat(string $todayDateFormat): User
    {
        $this->todayDateFormat = $todayDateFormat;
        return $this;
    }

    public function getDurationFormat(): string
    {
        return $this->durationFormat;
    }

    public function setDurationFormat(string $durationFormat): self
    {
        $this->durationFormat = $durationFormat;
        return $this;
    }

    /**
     * @return Collection|Task[]
     */
    public function getTasks(): Collection
    {
        return $this->tasks;
    }

    public function addTask(Task $task): self
    {
        if (!$this->tasks->contains($task)) {
            $this->tasks[] = $task;
            $task->setCreatedBy($this);
        }

        return $this;
    }

    public function removeTask(Task $task): self
    {
        if ($this->tasks->removeElement($task)) {
            // set the owning side to null (unless already changed)
            if ($task->getCreatedBy() === $this) {
                $task->setCreatedBy(null);
            }
        }

        return $this;
    }
}

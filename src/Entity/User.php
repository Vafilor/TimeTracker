<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use App\Traits\CreateTimestampableTrait;
use App\Traits\UUIDTrait;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

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
     */
    private string $timezone;

    /**
     * @ORM\Column(type="string")
     */
    private string $dateFormat;

    /**
     * @ORM\Column(type="string")
     */
    private string $dateTimeFormat;

    /**
     * @ORM\Column(type="string")
     */
    private string $todayDateTimeFormat;

    /**
     * @ORM\Column(type="string")
     */
    private string $durationFormat;

    /**
     * @ORM\OneToMany(targetEntity=Task::class, mappedBy="assignedTo", orphanRemoval=true)
     * @var Task[]|Collection
     */
    private Collection $tasks;

    /**
     * @ORM\OneToMany(targetEntity=TimeEntry::class, mappedBy="assignedTo")
     * @var TimeEntry[]|Collection
     */
    private Collection $timeEntries;

    /**
     * @ORM\OneToMany(targetEntity=Note::class, mappedBy="assignedTo")
     * @var TimeEntry[]|Collection
     */
    private Collection $notes;

    public function __construct(DateTime $createdAt = null)
    {
        parent::__construct();

        $this->id = Uuid::uuid4();
        $this->timeEntries = new ArrayCollection();
        $this->timezone = "America/Los_Angeles";
        $this->dateFormat = 'm/d/Y';
        $this->dateTimeFormat = 'm/d/Y h:i:s A';
        $this->todayDateTimeFormat = 'h:i:s A';
        $this->durationFormat = '%hh %Im %Ss';
        $this->tasks = new ArrayCollection();
        $this->timeEntries = new ArrayCollection();
        $this->notes = new ArrayCollection();

        $this->markCreated($createdAt);
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
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

    public function getDateTimeFormat(): string
    {
        return $this->dateTimeFormat;
    }

    public function setDateTimeFormat(string $dateTimeFormat): User
    {
        $this->dateTimeFormat = $dateTimeFormat;
        return $this;
    }

    public function getTodayDateTimeFormat(): string
    {
        return $this->todayDateTimeFormat;
    }

    public function setTodayDateTimeFormat(string $todayDateTimeFormat): User
    {
        $this->todayDateTimeFormat = $todayDateTimeFormat;
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
}

<?php

namespace App\Entity;

use App\Repository\UserRepository;
use App\Traits\UUIDTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Intl\Timezones;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @ORM\Table(name="users")
 */
class User extends BaseUser
{
    use UUIDTrait;

    /**
     * @ORM\OneToMany(targetEntity=TimeEntry::class, mappedBy="owner")
     */
    private $timeEntries;

    /**
     * @ORM\Column(type="string")
     */
    private $timezone;

    public function __construct()
    {
        parent::__construct();
        $this->timeEntries = new ArrayCollection();
        $this->timezone = "America/Los_Angeles";
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

    /**
     * @return string
     */
    public function getTimezone(): string
    {
        return $this->timezone;
    }

    /**
     * @param string $timezone
     * @return User
     */
    public function setTimezone(string $timezone): self
    {
        $this->timezone = $timezone;
        return $this;
    }
}

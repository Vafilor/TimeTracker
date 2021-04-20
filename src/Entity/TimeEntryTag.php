<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TimeEntryTagRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=TimeEntryTagRepository::class)
 */
class TimeEntryTag
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=TimeEntry::class, inversedBy="timeEntryTags")
     * @ORM\JoinColumn(nullable=false)
     */
    private $timeEntry;

    /**
     * @ORM\ManyToOne(targetEntity=Tag::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $tag;

    public function __construct(TimeEntry $timeEntry, Tag $tag)
    {
        $this->timeEntry = $timeEntry;
        $this->tag = $tag;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTimeEntry(): TimeEntry
    {
        return $this->timeEntry;
    }

    public function getTag(): Tag
    {
        return $this->tag;
    }
}

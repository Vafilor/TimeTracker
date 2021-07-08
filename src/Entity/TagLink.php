<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TagLinkRepository;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

/**
 * @ORM\Entity(repositoryClass=TagLinkRepository::class)
 */
class TagLink
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id;

    /**
     * @ORM\ManyToOne(targetEntity=TimeEntry::class, inversedBy="tagLinks")
     * @ORM\JoinColumn(nullable=true)
     */
    private ?TimeEntry $timeEntry;

    /**
     * @ORM\ManyToOne(targetEntity=Timestamp::class, inversedBy="tagLinks")
     * @ORM\JoinColumn(nullable=true)
     */
    private ?Timestamp $timestamp;

    /**
     * @ORM\ManyToOne(targetEntity=Task::class, inversedBy="tagLinks")
     * @ORM\JoinColumn(nullable=true)
     */
    private ?Task $task;

    /**
     * @ORM\ManyToOne(targetEntity=Note::class, inversedBy="tagLinks")
     * @ORM\JoinColumn(nullable=true)
     */
    private ?Note $note;

    /**
     * @ORM\ManyToOne(targetEntity=Tag::class, inversedBy="tagLinks")
     * @ORM\JoinColumn(nullable=false)
     */
    private Tag $tag;

    /**
     * @ORM\ManyToOne(targetEntity=Statistic::class, inversedBy="tagLinks")
     * @ORM\JoinColumn(nullable=true)
     */
    private ?Statistic $statistic;

    public function __construct(TimeEntry|Timestamp|Task|Statistic|Note $resource, Tag $tag)
    {
        $this->tag = $tag;

        if ($resource instanceof TimeEntry) {
            $this->timeEntry = $resource;
        } elseif ($resource instanceof Timestamp) {
            $this->timestamp = $resource;
        } elseif ($resource instanceof Task) {
            $this->task = $resource;
        } elseif ($resource instanceof Statistic) {
            $this->statistic = $resource;
        } elseif ($resource instanceof Note) {
            $this->note = $resource;
        } else {
            throw new InvalidArgumentException("Resource for TagLink not supported");
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTimeEntry(): ?TimeEntry
    {
        return $this->timeEntry;
    }

    public function getTimestamp(): ?Timestamp
    {
        return $this->timestamp;
    }

    public function getTask(): ?Task
    {
        return$this->task;
    }

    public function getStatistic(): ?Statistic
    {
        return $this->statistic;
    }

    public function getNote(): ?Note
    {
        return $this->note;
    }

    public function getTag(): Tag
    {
        return $this->tag;
    }
}

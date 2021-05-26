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
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=TimeEntry::class, inversedBy="tagLinks")
     * @ORM\JoinColumn(nullable=true)
     */
    private $timeEntry;

    /**
     * @ORM\ManyToOne(targetEntity=Timestamp::class, inversedBy="tagLinks")
     * @ORM\JoinColumn(nullable=true)
     */
    private $timestamp;

    /**
     * @ORM\ManyToOne(targetEntity=Tag::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $tag;

    public function __construct(mixed $resource, Tag $tag)
    {
        $this->tag = $tag;

        if ($resource instanceof TimeEntry) {
            $this->timeEntry = $resource;
        } elseif ($resource instanceof Timestamp) {
            $this->timestamp = $resource;
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

    public function getTag(): Tag
    {
        return $this->tag;
    }
}

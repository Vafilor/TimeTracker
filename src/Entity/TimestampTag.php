<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TimestampTagRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=TimestampTagRepository::class)
 */
class TimestampTag
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Tag::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $tag;

    /**
     * @ORM\ManyToOne(targetEntity=Timestamp::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $timestamp;

    public function __construct(Timestamp $timestamp, Tag $tag) {
        $this->timestamp = $timestamp;
        $this->tag = $tag;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTag(): Tag
    {
        return $this->tag;
    }

    public function setTag(Tag $tag): self
    {
        $this->tag = $tag;

        return $this;
    }

    public function getTimestamp(): ?Timestamp
    {
        return $this->timestamp;
    }

    public function setTimestamp(Timestamp $timestamp): self
    {
        $this->timestamp = $timestamp;

        return $this;
    }
}

<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TimestampRepository;
use App\Traits\UUIDTrait;
use DateTime;
use DateTimeZone;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

/**
 * @ORM\Entity(repositoryClass=TimestampRepository::class)
 */
class Timestamp
{
    use UUIDTrait;

    /**
     * @ORM\Column(type="datetimetz")
     * @var DateTime
     */
    protected $createdAt;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $createdBy;

    /**
     * @ORM\OneToMany(targetEntity=TimestampTag::class, mappedBy="timestamp", orphanRemoval=true)
     */
    private $timestampTags;

    public function __construct(User $creator)
    {
        $this->id = Uuid::uuid4();
        $this->createdAt = new DateTime('now', new DateTimeZone('UTC'));
        $this->createdBy = $creator;
        $this->timestampTags = new ArrayCollection();
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): Timestamp
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getCreatedBy(): User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(User $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * @return Collection|TimestampTag[]
     */
    public function getTimestampTags(): Collection
    {
        return $this->timestampTags;
    }

    /**
     * Add a timestampTag to this Timestamp. This does not add it to the database,
     * it is purely for this object in memory.
     * To persist the TimestampTag it must be persisted outside of this method.
     *
     * @param TimestampTag $timestampTag
     * @return $this
     */
    public function addTimestampTag(TimestampTag $timestampTag): self
    {
        $this->timestampTags->add($timestampTag);

        return $this;
    }

    /**
     * @return Collection|Tag[]
     */
    public function getTags(): array|Collection
    {
        $tags = [];

        foreach ($this->getTimestampTags() as $timestampTag) {
            $tags[] = $timestampTag->getTag();
        }

        usort($tags, fn (Tag $a, Tag $b) => strcmp($a->getName(), $b->getName()));

        return $tags;
    }
}

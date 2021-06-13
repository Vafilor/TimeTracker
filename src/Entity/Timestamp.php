<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TimestampRepository;
use App\Traits\CreateTimestampableTrait;
use App\Traits\TaggableTrait;
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
    use CreateTimestampableTrait;
    use TaggableTrait;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $createdBy;

    /**
     * @ORM\OneToMany(targetEntity=TagLink::class, mappedBy="timestamp", orphanRemoval=true)
     */
    private $tagLinks;

    public function __construct(User $creator)
    {
        $this->id = Uuid::uuid4();
        $this->markCreated();
        $this->createdBy = $creator;
        $this->tagLinks = new ArrayCollection();
    }

    public function getCreatedBy(): User
    {
        return $this->createdBy;
    }

    public function wasCreatedBy(User $user): bool
    {
        return $this->getCreatedBy()->equalIds($user);
    }

    public function setCreatedBy(User $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * Add a TagLink to this Timestamp. This does not add it to the database,
     * it is purely for this object in memory.
     * To persist the TagLink it must be persisted outside of this method.
     *
     * @param TagLink $tagLink
     * @return $this
     */
    public function addTagLink(TagLink $tagLink): self
    {
        $this->tagLinks->add($tagLink);

        return $this;
    }
}

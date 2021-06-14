<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TimestampRepository;
use App\Traits\AssignableToUserTrait;
use App\Traits\CreateTimestampableTrait;
use App\Traits\TaggableTrait;
use App\Traits\UUIDTrait;
use Doctrine\Common\Collections\ArrayCollection;
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
    use AssignableToUserTrait;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     * @ORM\JoinColumn(nullable=false)
     * @var User
     */
    private $assignedTo;

    /**
     * @ORM\OneToMany(targetEntity=TagLink::class, mappedBy="timestamp", orphanRemoval=true)
     */
    private $tagLinks;

    public function __construct(User $assignedTo)
    {
        $this->id = Uuid::uuid4();
        $this->markCreated();
        $this->assignTo($assignedTo);
        $this->tagLinks = new ArrayCollection();
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

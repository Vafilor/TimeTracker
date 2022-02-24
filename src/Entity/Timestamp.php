<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TimestampRepository;
use App\Traits\AssignableToUserTrait;
use App\Traits\CreateTimestampableTrait;
use App\Traits\TaggableTrait;
use App\Traits\UUIDTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity(repositoryClass: TimestampRepository::class)]
class Timestamp
{
    use UUIDTrait;
    use CreateTimestampableTrait;
    use TaggableTrait;
    use AssignableToUserTrait;

    /**
     * Any content you wish to add to a timestamp,
     * like "server reports out of memory error this time"
     */
    #[ORM\Column(type: "text")]
    private string $description;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $assignedTo;

    /**
     * @var TagLink[]|Collection
     */
    #[ORM\OneToMany(mappedBy: "timestamp", targetEntity: TagLink::class, orphanRemoval: true)]
    private Collection $tagLinks;

    /**
     * @var StatisticValue[]|Collection
     */
    #[ORM\OneToMany(mappedBy: "timestamp", targetEntity: StatisticValue::class, orphanRemoval: true)]
    private Collection $statisticValues;

    public function __construct(User $assignedTo)
    {
        $this->id = Uuid::uuid4();
        $this->markCreated();
        $this->assignTo($assignedTo);
        $this->description = '';
        $this->tagLinks = new ArrayCollection();
        $this->statisticValues = new ArrayCollection();
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

    /**
     * @return StatisticValue[]|Collection
     */
    public function getStatisticValues(): Collection|array
    {
        return $this->statisticValues;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }
}

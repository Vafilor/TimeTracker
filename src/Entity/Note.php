<?php

namespace App\Entity;

use App\Repository\NoteRepository;
use App\Traits\AssignableToUserTrait;
use App\Traits\CreateTimestampableTrait;
use App\Traits\TaggableTrait;
use App\Traits\UpdateTimestampableTrait;
use App\Traits\UUIDTrait;
use DateTime;
use DateTimeZone;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

#[ORM\Entity(repositoryClass: NoteRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Note
{
    use UUIDTrait;
    use CreateTimestampableTrait;
    use UpdateTimestampableTrait;
    use AssignableToUserTrait;
    use TaggableTrait;

    #[ORM\Column(type: 'string', length: 255)]
    private string $title;

    #[ORM\Column(type: 'text')]
    private string $content;

    /**
     * This is the date the note is for. So, if I'm writing down some notes on what happened on
     * 1.1.2020, I can set that to this variable. I may remember things on different times and add to them
     * later, or I may add a note for a day later.
     */
    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTime $forDate;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'notes')]
    #[ORM\JoinColumn(nullable: false)]
    private User $assignedTo;

    /**
     * @var TagLink[]|Collection
     */
    #[ORM\OneToMany(targetEntity: TagLink::class, mappedBy: 'note')]
    private Collection $tagLinks;

    public function __construct(User $assignedTo, string $title = '', string $content = '')
    {
        $this->id = Uuid::uuid4();
        $this->markCreated();
        $this->updatedAt = $this->createdAt;
        $this->title = $title;
        $this->content = $content;
        $this->assignedTo = $assignedTo;
        $this->tagLinks = new ArrayCollection();
        $this->forDate = null;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getForDate(): ?DateTime
    {
        return $this->forDate;
    }

    public function setForDate(?DateTime $forDate): self
    {
        $forDate?->setTimezone(new DateTimeZone('UTC'));

        $this->forDate = $forDate;

        return $this;
    }
}

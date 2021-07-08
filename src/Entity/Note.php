<?php

namespace App\Entity;

use App\Repository\NoteRepository;
use App\Traits\AssignableToUserTrait;
use App\Traits\CreateTimestampableTrait;
use App\Traits\TaggableTrait;
use App\Traits\UpdateTimestampableTrait;
use App\Traits\UUIDTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

/**
 * @ORM\Entity(repositoryClass=NoteRepository::class)
 */
class Note
{
    use UUIDTrait;
    use CreateTimestampableTrait;
    use UpdateTimestampableTrait;
    use AssignableToUserTrait;
    use TaggableTrait;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $title;

    /**
     * @ORM\Column(type="text")
     */
    private string $content;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="notes")
     * @ORM\JoinColumn(nullable=false)
     */
    private User $assignedTo;

    /**
     * @ORM\OneToMany(targetEntity=TagLink::class, mappedBy="note")
     * @var TagLink[]|Collection
     */
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
}

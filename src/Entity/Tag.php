<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TagRepository;
use App\Traits\AssignableToUserTrait;
use App\Traits\CreateTimestampableTrait;
use App\Traits\UUIDTrait;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

#[ORM\Entity(repositoryClass: TagRepository::class)]
class Tag
{
    use UUIDTrait;
    use CreateTimestampableTrait;
    use AssignableToUserTrait;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 255)]
    private string $canonicalName;

    /**
     * Hex color string, includes #. e.g. #FF0000.
     */
    #[ORM\Column(type: 'string', length: 7)]
    private string $color;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $assignedTo;

    /**
     * @var TagLink[]|Collection
     */
    #[ORM\OneToMany(mappedBy: 'tag', targetEntity: TagLink::class, orphanRemoval: true)]
    private Collection $tagLinks;

    public function __construct(
        User $assignedTo,
        string $name,
        string $color = '#5d5d5d',
        DateTime $createdAt = null
    ) {
        $this->id = Uuid::uuid4();
        $this->assignTo($assignedTo);
        $this->setName($name);
        $this->color = $color;
        $this->markCreated($createdAt);
        $this->tagLinks = new ArrayCollection();
    }

    private function canonicalizeName(string $name): string
    {
        return trim(strtolower($name));
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCanonicalName(): string
    {
        return $this->canonicalName;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        $this->canonicalName = $this->canonicalizeName($name);

        if (0 === strlen($this->canonicalName)) {
            throw new InvalidArgumentException('Name can not be blank once whitespace is removed.');
        }

        return $this;
    }

    public function setColor(string $color): self
    {
        $this->color = $color;

        return $this;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function getTagLinks(): Collection
    {
        return $this->tagLinks;
    }
}

<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TagRepository;
use App\Traits\CreateTimestampableTrait;
use App\Traits\UUIDTrait;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

/**
 * @ORM\Entity(repositoryClass=TagRepository::class)
 */
class Tag
{
    use UUIDTrait;
    use CreateTimestampableTrait;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     * @var string
     */
    private $canonicalName;

    /**
     * @ORM\Column(type="string", length=7)
     * @var string
     *
     * Hex color string, includes #. e.g. #FF0000
     */
    private $color;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $createdBy;

    public function __construct(
        User $createdBy,
        string $name,
        string $color = '#5d5d5d',
        DateTime $createdAt = null
    ) {
        $this->id = Uuid::uuid4();
        $this->createdBy = $createdBy;
        $this->setName($name);
        $this->color = $color;
        $this->createdBy = $createdBy;
        $this->markCreated($createdAt);
    }

    private function canonicalizeName(string $name): string
    {
        return strtolower($name);
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
}

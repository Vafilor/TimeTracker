<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TagRepository;
use App\Traits\UUIDTrait;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

/**
 * @ORM\Entity(repositoryClass=TagRepository::class)
 */
class Tag
{
    use UUIDTrait;

    /**
     * @ORM\Column(type="string", unique=True, length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=7)
     * @var string
     *
     * Hex color string, includes #. e.g. #FF0000
     */
    private $color;

    public function __construct(string $name, string $color = '#5d5d5d')
    {
        $this->id = Uuid::uuid4();
        $this->name = $name;
        $this->color = $color;
    }

    public function getName(): ?string
    {
        return $this->name;
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
}

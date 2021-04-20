<?php

namespace App\Entity;

use App\Repository\TagRepository;
use App\Traits\UUIDTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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

    public function __construct(string $name)
    {
        $this->id = Uuid::uuid4();
        $this->name = $name;
        $this->timeEntries = new ArrayCollection();
    }

    public function getName(): ?string
    {
        return $this->name;
    }
}

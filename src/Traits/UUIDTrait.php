<?php

declare(strict_types=1);

namespace App\Traits;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

trait UUIDTrait
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    protected Uuid $id;

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getIdString(): string
    {
        return $this->id->toRfc4122();
    }

    public function setId(Uuid $uuid): static
    {
        $this->id = $uuid;

        return $this;
    }

    public function equalIds($that): bool
    {
        return $this->getId()->equals($that->getId());
    }

    public function equals($that): bool
    {
        return $this->equalIds($that);
    }
}

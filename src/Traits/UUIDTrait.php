<?php

declare(strict_types=1);

namespace App\Traits;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

trait UUIDTrait
{
    #[ORM\Id]
    #[ORM\Column(type: "uuid", unique: true)]
    protected UuidInterface $id;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getIdString(): string
    {
        return $this->id->toString();
    }

    public function setId(UuidInterface $uuid): static
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

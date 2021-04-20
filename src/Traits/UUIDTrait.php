<?php

declare(strict_types=1);

namespace App\Traits;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

trait UUIDTrait
{
    /**
     * @var UuidInterface
     * @ORM\Id()
     * @ORM\Column(type="uuid", unique=true)
     */
    protected $id;

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getIdString(): string
    {
        return $this->id->toString();
    }

    public function setId(UuidInterface $uuid)
    {
        $this->id = $uuid;
    }

    public function equalIds($that)
    {
        return $this->getId()->equals($that->getId());
    }

    public function equals($that)
    {
        return $this->equalIds($that);
    }
}

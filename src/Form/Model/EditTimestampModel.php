<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Entity\Timestamp;
use DateTime;

class EditTimestampModel
{
    private ?DateTime $createdAt;
    private ?string $description;

    public static function fromEntity(Timestamp $timestamp): self
    {
        $model = new EditTimestampModel();
        $model->setCreatedAt($timestamp->getCreatedAt());
        $model->setDescription($timestamp->getDescription());

        return $model;
    }

    public function __construct()
    {
    }

    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function hasDescription(): bool
    {
        return null !== $this->description;
    }

    public function getDescription(): string
    {
        if (null === $this->description) {
            return '';
        }
        
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }
}

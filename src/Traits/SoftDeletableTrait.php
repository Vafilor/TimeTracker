<?php

declare(strict_types=1);

namespace App\Traits;

use DateTime;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;

trait SoftDeletableTrait
{
    #[ORM\Column(type: 'datetime', nullable: true)]
    protected ?DateTime $deletedAt = null;

    public function getDeletedAt(): ?DateTime
    {
        return $this->deletedAt;
    }

    public function isDeleted(): bool
    {
        return !is_null($this->deletedAt);
    }

    public function softDelete(DateTime $when = null): static
    {
        if (is_null($when)) {
            $when = new DateTime('now');
        }

        $when->setTimezone(new DateTimeZone('UTC'));

        $this->deletedAt = $when;

        return $this;
    }
}

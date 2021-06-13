<?php

declare(strict_types=1);

namespace App\Traits;

use DateTime;
use DateTimeZone;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;

trait UpdateTimestampableTrait
{
    /**
     * @ORM\Column(type="datetimetz")
     * @var DateTime
     */
    protected $updatedAt;

    public function setUpdatedAt(DateTime $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @ORM\PreUpdate()
     */
    public function onPreUpdate(PreUpdateEventArgs $event)
    {
        $this->updatedAt = new DateTime('now', new DateTimeZone('UTC'));
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }
}

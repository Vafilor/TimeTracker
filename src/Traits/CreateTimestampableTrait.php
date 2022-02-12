<?php

declare(strict_types=1);

namespace App\Traits;

use DateTime;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;

trait CreateTimestampableTrait
{
    /**
     * @ORM\Column(type="datetime")
     */
    protected DateTime $createdAt;

    /**
     * markCreated sets the createdAt value to the input in UTC, or, if it is null, to
     * 'now' in UTC.
     *
     * @param DateTime|null $createdAt
     * @return static
     */
    public function markCreated(?DateTime $createdAt = null): static
    {
        if (is_null($createdAt)) {
            $createdAt = new DateTime('now');
        }

        return $this->setCreatedAt($createdAt);
    }

    public function setCreatedAt(DateTime $createdAt): static
    {
        $createdAt->setTimezone(new DateTimeZone('UTC'));
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getCreatedAt(): DateTime
    {
        return clone $this->createdAt;
    }
}

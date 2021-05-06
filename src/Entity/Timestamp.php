<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TimestampRepository;
use App\Traits\UUIDTrait;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

/**
 * @ORM\Entity(repositoryClass=TimestampRepository::class)
 */
class Timestamp
{
    use UUIDTrait;

    /**
     * @ORM\Column(type="datetimetz")
     * @var DateTime
     */
    protected $createdAt;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $createdBy;

    public function __construct(User $creator)
    {
        $this->id = Uuid::uuid4();
        $this->createdAt = new DateTime('now', new DateTimeZone('UTC'));
        $this->createdBy = $creator;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): Timestamp
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getCreatedBy(): User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(User $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }
}

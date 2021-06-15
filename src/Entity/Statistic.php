<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\StatisticRepository;
use App\Traits\AssignableToUserTrait;
use App\Traits\CreateTimestampableTrait;
use App\Traits\TaggableTrait;
use App\Traits\UUIDTrait;
use App\Util\TimeType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

/**
 * @ORM\Entity(repositoryClass=StatisticRepository::class)
 */
class Statistic
{
    use UUIDTrait;
    use CreateTimestampableTrait;
    use TaggableTrait;
    use AssignableToUserTrait;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @var string
     */
    private $icon;

    /**
     * @ORM\Column(type="string", length=255)
     * @var string
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     * @var string
     */
    private $canonicalName;

    /**
     * @ORM\Column(type="text")
     * @var string
     */
    private $description;

    /**
     * @ORM\Column(type="string", length=255)
     * @var string
     */
    private $valueType;

    /**
     * @ORM\Column(type="string", length=255)
     * @var string One of 'instance' | 'duration'
     */
    private $timeType;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     * @ORM\JoinColumn(nullable=false)
     * @var User
     */
    private $assignedTo;

    /**
     * @ORM\OneToMany(targetEntity=TagLink::class, mappedBy="timeEntry")
     * @var TagLink[]
     */
    private $tagLinks;

    public function __construct(User $assignedTo, string $name)
    {
        $this->id = Uuid::uuid4();
        $this->assignTo($assignedTo);
        $this->setName($name);
        $this->markCreated();
        $this->description = '';
        $this->valueType = 'int';
        $this->timeType = TimeType::instant;
        $this->tagLinks = new ArrayCollection();
    }

    private function canonicalizeName(string $name): string
    {
        return trim(strtolower($name));
    }

    public function getCanonicalName(): string
    {
        return $this->canonicalName;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        $this->canonicalName = $this->canonicalizeName($name);

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getValueType(): string
    {
        return $this->valueType;
    }

    public function setValueType(string $valueType): self
    {
        $this->valueType = $valueType;

        return $this;
    }

    public function getTimeType(): string
    {
        return $this->timeType;
    }

    public function setTimeType(string $timeType): self
    {
        if (!TimeType::isValid($timeType)) {
            throw new InvalidArgumentException(TimeType::invalidErrorMessage($timeType));
        }

        $this->timeType = $timeType;

        return $this;
    }
}

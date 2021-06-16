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
     */
    private string $icon;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $name;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $canonicalName;

    /**
     * @ORM\Column(type="text")
     */
    private string $description;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $valueType;

    /**
     * @ORM\Column(type="string", length=255)
     * @var string One of 'instance' | 'interval'
     */
    private string $timeType;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private User $assignedTo;

    /**
     * @ORM\OneToMany(targetEntity=TagLink::class, mappedBy="timeEntry")
     * @var TagLink[]
     */
    private $tagLinks;

    public static function canonicalizeName(string $name): string
    {
        return trim(strtolower($name));
    }

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

        $this->canonicalName = self::canonicalizeName($name);

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(string $icon): self
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

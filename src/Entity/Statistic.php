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
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

 #[ORM\Entity(repositoryClass: StatisticRepository::class)]
class Statistic
{
    use UUIDTrait;
    use CreateTimestampableTrait;
    use TaggableTrait;
    use AssignableToUserTrait;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $icon;

    #[ORM\Column(type: "string", length: 255)]
    private string $name;

    #[ORM\Column(type: "string", length: 255)]
    private string $canonicalName;

    #[ORM\Column(type: "text")]
    private string $description;

    /**
     * Hex color string, includes #. e.g. #FF0000
     */
    #[ORM\Column(type:"string", length:7)]
    private string $color;

    /**
     * The unit of the statistic, like meters, cups, beats per minute.
     */
    #[ORM\Column(type: "string")]
    private string $unit;

    /**
     * @var string One of 'instance' | 'interval'
     */
    #[ORM\Column(type: "string", length: 255)]
    private string $timeType;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $assignedTo;

    /**
     * @var TagLink[]|Collection
     */
    #[ORM\OneToMany(mappedBy: "statistic", targetEntity: TagLink::class, orphanRemoval: true)]
    private Collection $tagLinks;

    /**
     * @var StatisticValue[]|Collection
     */
    #[ORM\OneToMany(mappedBy: "statistic", targetEntity: StatisticValue::class, orphanRemoval: true)]
    private Collection $statisticValues;

    public static function canonicalizeName(string $name): string
    {
        return trim(strtolower($name));
    }

    public function __construct(User $assignedTo, string $name, string $timeType = TimeType::INSTANT)
    {
        $this->id = Uuid::uuid4();
        $this->assignTo($assignedTo);
        $this->setName($name);
        $this->markCreated();
        $this->description = '';
        $this->setTimeType($timeType);
        $this->tagLinks = new ArrayCollection();
        $this->statisticValues = new ArrayCollection();
        $this->icon = null;
        $this->color = '#000000';
        $this->unit = '';
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

        if (strlen($this->canonicalName) === 0) {
            throw new InvalidArgumentException('Name can not be blank once whitespace is removed.');
        }

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

    public function hasIcon(): bool
    {
        return !is_null($this->icon);
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

    public function getColor(): string
    {
        return $this->color;
    }

    public function setColor(string $color): self
    {
        $this->color = $color;
        return $this;
    }

    public function getUnit(): string
    {
        return $this->unit;
    }

    public function setUnit(string $unit): self
    {
        $this->unit = $unit;
        return $this;
    }
}

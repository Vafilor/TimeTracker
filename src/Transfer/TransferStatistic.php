<?php

declare(strict_types=1);

namespace App\Transfer;

use App\Entity\Statistic;
use App\Entity\User;
use App\Util\DateTimeUtil;
use Ramsey\Uuid\Uuid;

class TransferStatistic
{
    public string $id;

    public int $createdAt;

    public string $assignedTo;

    public string $name;

    public string $canonicalName; // Technically not needed, but handy to see in json.

    public string $description;

    public string $color;

    public string $unit;

    public string $timeType;

    public ?string $icon = null;

    /**
     * @var TransferTagLink[]
     */
    public array $tags = [];

    public static function fromEntity(Statistic $statistic): TransferStatistic
    {
        $transfer = new TransferStatistic();

        $transfer->id = $statistic->getIdString();
        $transfer->createdAt = $statistic->getCreatedAt()->getTimestamp();
        $transfer->name = $statistic->getName();
        $transfer->canonicalName = $statistic->getCanonicalName();
        $transfer->description = $statistic->getDescription();
        $transfer->color = $statistic->getColor();
        $transfer->unit = $statistic->getUnit();
        $transfer->timeType = $statistic->getTimeType();
        $transfer->icon = $statistic->getIcon();
        $transfer->assignedTo = $statistic->getAssignedTo()->getUsername();
        $transfer->tags = TransferTagLink::fromTags($statistic->getTags());

        return $transfer;
    }

    /**
     * @param Statistic[]|iterable $entities
     *
     * @return TransferStatistic[]
     */
    public static function fromEntities(iterable $entities): array
    {
        $items = [];
        foreach ($entities as $entity) {
            $items[] = self::fromEntity($entity);
        }

        return $items;
    }

    public function toEntity(User $assignedTo): Statistic
    {
        // No need to set canonical name as that is automatically handled by the class via setting the name.
        $statistic = new Statistic($assignedTo, $this->name, $this->timeType);
        $statistic->setId(Uuid::fromString($this->id));
        $statistic->setCreatedAt(DateTimeUtil::dateFromTimestamp($this->createdAt));
        $statistic->setDescription($this->description);
        $statistic->setTimeType($this->timeType);
        $statistic->setIcon($this->icon);
        $statistic->setColor($this->color);
        $statistic->setUnit($this->unit);

        return $statistic;
    }
}

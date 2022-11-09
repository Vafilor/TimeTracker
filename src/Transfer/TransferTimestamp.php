<?php

declare(strict_types=1);

namespace App\Transfer;

use App\Entity\Timestamp;
use App\Entity\User;
use App\Util\DateTimeUtil;
use Ramsey\Uuid\Uuid;

class TransferTimestamp
{
    public string $id;

    public int $createdAt;

    public string $assignedTo;

    public string $description;

    /**
     * @var TransferTagLink[]
     */
    public array $tags = [];

    public function __construct()
    {
        $this->description = '';
    }

    public static function fromEntity(Timestamp $timestamp): TransferTimestamp
    {
        $transfer = new TransferTimestamp();

        $transfer->id = $timestamp->getIdString();
        $transfer->createdAt = $timestamp->getCreatedAt()->getTimestamp();
        $transfer->description = $timestamp->getDescription();
        $transfer->assignedTo = $timestamp->getAssignedTo()->getUsername();
        $transfer->tags = TransferTagLink::fromTags($timestamp->getTags());

        return $transfer;
    }

    /**
     * @param Timestamp[]|iterable $entities
     *
     * @return TransferTimestamp[]
     */
    public static function fromEntities(iterable $entities): array
    {
        $items = [];
        foreach ($entities as $entity) {
            $items[] = self::fromEntity($entity);
        }

        return $items;
    }

    public function toEntity(User $assignedTo): Timestamp
    {
        $entity = new Timestamp($assignedTo);
        $entity->setId(Uuid::fromString($this->id));
        $entity->setCreatedAt(DateTimeUtil::dateFromTimestamp($this->createdAt));
        $entity->setDescription($this->description);

        return $entity;
    }
}

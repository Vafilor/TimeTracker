<?php

declare(strict_types=1);

namespace App\Transfer;

use App\Entity\Timestamp;

class TransferTimestamp
{
    public int $createdAt;
    public string $createdBy;

    /**
     * @var TransferTagLink[]
     */
    public array $tags = [];

    public static function fromEntity(Timestamp $timestamp): TransferTimestamp
    {
        $transfer = new TransferTimestamp();

        $transfer->createdAt = $timestamp->getCreatedAt()->getTimestamp();
        $transfer->createdBy = $timestamp->getCreatedBy()->getUsername();
        $transfer->tags = TransferTagLink::fromTags($timestamp->getTags());

        return $transfer;
    }

    /**
     * @param Timestamp[]|iterable $entities
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
}

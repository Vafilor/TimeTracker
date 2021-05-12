<?php

declare(strict_types=1);

namespace App\Manager;

use App\Entity\Timestamp;
use App\Entity\TimestampTag;
use Doctrine\ORM\EntityManagerInterface;

class TimestampManager
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * repeat creates a new Timestamp from an existing one, but using the current time.
     * The tags and other properties are copied from the input timestamp.
     *
     * The new entities are persisted to doctrine, but not flushed.
     *
     * @param Timestamp $timestamp
     * @return Timestamp
     */
    public function repeat(Timestamp $timestamp): Timestamp
    {
        $newTimestamp = new Timestamp($timestamp->getCreatedBy());
        $this->entityManager->persist($newTimestamp);

        foreach ($timestamp->getTimestampTags() as $timestampTag) {
            $newTimestampTag = new TimestampTag($newTimestamp, $timestampTag->getTag());
            $newTimestamp->addTimestampTag($newTimestampTag);
            $this->entityManager->persist($newTimestampTag);
        }

        return $newTimestamp;
    }
}
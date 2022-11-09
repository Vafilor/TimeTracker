<?php

declare(strict_types=1);

namespace App\Manager;

use App\Entity\TagLink;
use App\Entity\Timestamp;
use Doctrine\ORM\EntityManagerInterface;

class TimestampManager
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * repeat creates a new Timestamp from an existing one, but using the current time.
     * The tags and other properties are copied from the input timestamp.
     *
     * The new entities are persisted to doctrine, but not flushed.
     */
    public function repeat(Timestamp $timestamp): Timestamp
    {
        $newTimestamp = new Timestamp($timestamp->getAssignedTo());
        $this->entityManager->persist($newTimestamp);

        foreach ($timestamp->getTagLinks() as $tagLink) {
            $newTagLink = new TagLink($newTimestamp, $tagLink->getTag());
            $newTimestamp->addTagLink($newTagLink);
            $this->entityManager->persist($newTagLink);
        }

        return $newTimestamp;
    }
}

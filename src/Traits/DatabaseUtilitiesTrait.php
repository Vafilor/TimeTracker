<?php

declare(strict_types=1);

namespace App\Traits;

use Doctrine\Persistence\ManagerRegistry;

trait DatabaseUtilitiesTrait
{
    abstract protected function getDoctrine(): ManagerRegistry;

    /**
     * Utility method to get the doctrine manager, persist the input object.
     */
    public function persist(mixed $obj, bool $flush = false): void
    {
        $manager = $this->getDoctrine()->getManager();
        $manager->persist($obj);

        if ($flush) {
            $manager->flush();
        }
    }

    /**
     * Utility method to get the doctrine manager, and flush the entity manager.
     */
    public function flush(): void
    {
        $this->getDoctrine()->getManager()->flush();
    }

    /**
     * Utility method to get the doctrine manager, remove the input object.
     */
    public function doctrineRemove(mixed $obj, bool $flush = false): void
    {
        $manager = $this->getDoctrine()->getManager();
        $manager->remove($obj);

        if ($flush) {
            $manager->flush();
        }
    }
}

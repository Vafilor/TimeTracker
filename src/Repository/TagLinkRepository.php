<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\TagLink;
use App\Entity\TimeEntry;
use App\Traits\FindOrExceptionTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method TagLink|null find($id, $lockMode = null, $lockVersion = null)
 * @method TagLink findOrException($id, $lockMode = null, $lockVersion = null)
 * @method TagLink|null findOneBy(array $criteria, array $orderBy = null)
 * @method TagLink findOneByOrException(array $criteria, array $orderBy = null)
 * @method TagLink[]    findAll()
 * @method TagLink[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TagLinkRepository extends ServiceEntityRepository
{
    use FindOrExceptionTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TagLink::class);
    }

    public function createDefaultQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('tag_link');
    }

    /**
     * Finds the TimeEntryTags for a TimeEntry with the tags fetched.
     *
     * @param TimeEntry $timeEntry
     * @return TagLink[]
     */
    public function findForTimeEntry(TimeEntry $timeEntry): array
    {
        return $this->createDefaultQueryBuilder()
                    ->addSelect('tag')
                    ->join('tag_link.tag', 'tag')
                    ->andWhere('tag_link.timeEntry = :timeEntry')
                    ->setParameter('timeEntry', $timeEntry)
                    ->getQuery()
                    ->getResult()
        ;
    }
}

<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\TimestampTag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method TimestampTag|null find($id, $lockMode = null, $lockVersion = null)
 * @method TimestampTag|null findOneBy(array $criteria, array $orderBy = null)
 * @method TimestampTag[]    findAll()
 * @method TimestampTag[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TimestampTagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TimestampTag::class);
    }

    // /**
    //  * @return TimestampTag[] Returns an array of TimestampTag objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?TimestampTag
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}

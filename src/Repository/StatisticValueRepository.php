<?php

namespace App\Repository;

use App\Entity\StatisticValue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method StatisticValue|null find($id, $lockMode = null, $lockVersion = null)
 * @method StatisticValue|null findOneBy(array $criteria, array $orderBy = null)
 * @method StatisticValue[]    findAll()
 * @method StatisticValue[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StatisticValueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StatisticValue::class);
    }

    // /**
    //  * @return StatisticValue[] Returns an array of StatisticValue objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?StatisticValue
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}

<?php

namespace App\Repository;

use App\Entity\StatisticValue;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
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

    public function createDefaultQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('statistic_value');
    }

    public function findWithUserQueryBuilder(User $user): QueryBuilder
    {
        return $this->createDefaultQueryBuilder()
                    ->addSelect('statistic')
                    ->join('statistic_value.statistic', 'statistic')
                    ->andWhere('statistic.assignedTo = :user')
                    ->setParameter('user', $user)
        ;
    }

    public function findWithUser(User $user)
    {
        return $this->findWithUserQueryBuilder($user)
                    ->getQuery()
                    ->getResult()
        ;
    }
}

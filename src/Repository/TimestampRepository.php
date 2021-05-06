<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Timestamp;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Timestamp|null find($id, $lockMode = null, $lockVersion = null)
 * @method Timestamp|null findOneBy(array $criteria, array $orderBy = null)
 * @method Timestamp[]    findAll()
 * @method Timestamp[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TimestampRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Timestamp::class);
    }

    public function createDefaultQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('timestamp');
    }

    public function findByUserQueryBuilder(User $user): QueryBuilder
    {
        return $this->createDefaultQueryBuilder()
                    ->andWhere('timestamp.createdBy = :user')
                    ->setParameter('user', $user)
        ;
    }
}

<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Timestamp;
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

    public function removeForTimestamp(Timestamp $timestamp)
    {
        $this->createQueryBuilder('timestamp_tag')
             ->andWhere('timestamp_tag.timestamp = :timestamp')
             ->setParameter('timestamp', $timestamp)
             ->delete()
             ->getQuery()
             ->getResult()
        ;
    }
}

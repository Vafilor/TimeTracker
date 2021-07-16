<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Statistic;
use App\Entity\User;
use App\Traits\FindByKeysInterface;
use App\Traits\FindByKeysTrait;
use App\Traits\FindOrExceptionTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Statistic|null find($id, $lockMode = null, $lockVersion = null)
 * @method Statistic|null findOneBy(array $criteria, array $orderBy = null)
 * @method Statistic[]    findAll()
 * @method Statistic[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method Statistic findOrException($id, $lockMode = null, $lockVersion = null)
 * @method Statistic findOneByOrException(array $criteria, array $orderBy = null)
 */
class StatisticRepository extends ServiceEntityRepository implements FindByKeysInterface
{
    use FindOrExceptionTrait;
    use FindByKeysTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Statistic::class);
    }

    public function createDefaultQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('statistic');
    }

    public function findWithUser(User $user): QueryBuilder
    {
        return $this->createDefaultQueryBuilder()
                    ->andWhere('statistic.assignedTo = :user')
                    ->setParameter('user', $user)
        ;
    }

    public function findWithUserNameQueryBuilder(User $user, string $name): QueryBuilder
    {
        return $this->findWithUser($user)
                    ->andWhere('statistic.name = :name')
                    ->setParameter('name', $name)
        ;
    }

    public function findWithUserNameCanonicalQueryBuilder(User $user, string $name): QueryBuilder
    {
        return $this->findWithUser($user)
            ->andWhere('statistic.canonicalName = :name')
            ->setParameter('name', $name)
            ;
    }

    public function findWithUserName(User $user, string $name): ?Statistic
    {
        return $this->findWithUserNameQueryBuilder($user, $name)
                    ->getQuery()
                    ->getOneOrNullResult()
        ;
    }

    public function findWithUserNameCanonical(User $user, string $name): ?Statistic
    {
        return $this->findWithUserNameCanonicalQueryBuilder($user, $name)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function preloadTags(?QueryBuilder $queryBuilder = null): QueryBuilder
    {
        if (is_null($queryBuilder)) {
            $queryBuilder = $this->createDefaultQueryBuilder();
        }

        return $queryBuilder->addSelect('tag, tag_link')
            ->leftJoin('statistic.tagLinks', 'tag_link')
            ->leftJoin('tag_link.tag', 'tag')
        ;
    }

    public function existsForUserName(User $user, string $name): bool
    {
        $result = $this->findWithUserName($user, $name);

        return !is_null($result);
    }
}

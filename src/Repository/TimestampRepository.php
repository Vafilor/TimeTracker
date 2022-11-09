<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Timestamp;
use App\Entity\User;
use App\Traits\FindByKeysInterface;
use App\Traits\FindByKeysTrait;
use App\Traits\FindOrExceptionTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Timestamp|null find($id, $lockMode = null, $lockVersion = null)
 * @method Timestamp      findOrException($id, $lockMode = null, $lockVersion = null)
 * @method Timestamp|null findOneBy(array $criteria, array $orderBy = null)
 * @method Timestamp      findOneByOrException(array $criteria, array $orderBy = null)
 * @method Timestamp[]    findAll()
 * @method Timestamp[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method Timestamp[] findByKeys(string $key, mixed $values);
 */
class TimestampRepository extends ServiceEntityRepository implements FindByKeysInterface
{
    use FindOrExceptionTrait;

    use FindByKeysTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Timestamp::class);
    }

    public function createDefaultQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('timestamp');
    }

    public function findCreateQueryBuilder($id): QueryBuilder
    {
        return $this->createDefaultQueryBuilder()
            ->andWhere('timestamp.id = :id')
            ->setParameter('id', $id)
        ;
    }

    public function findByUserQueryBuilder(User $user): QueryBuilder
    {
        return $this->createDefaultQueryBuilder()
                    ->andWhere('timestamp.assignedTo = :user')
                    ->setParameter('user', $user)
        ;
    }

    public function preloadTags(?QueryBuilder $queryBuilder = null): QueryBuilder
    {
        if (is_null($queryBuilder)) {
            $queryBuilder = $this->createDefaultQueryBuilder();
        }

        return $queryBuilder->addSelect('tag_link')
                            ->addSelect('tag')
                            ->leftJoin('timestamp.tagLinks', 'tag_link')
                            ->leftJoin('tag_link.tag', 'tag')
        ;
    }
}

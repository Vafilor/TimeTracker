<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Traits\FindByKeysTrait;
use App\Traits\FindOrExceptionTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User findOrException($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User findOneByOrException(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method User[] findByKeys(string $key, mixed $keys);
 */
class UserRepository extends ServiceEntityRepository
{
    use FindOrExceptionTrait;
    use FindByKeysTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function createDefaultQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('user');
    }
}

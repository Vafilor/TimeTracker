<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Tag;
use App\Entity\User;
use App\Traits\FindByKeysTrait;
use App\Traits\FindOrExceptionTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Tag|null find($id, $lockMode = null, $lockVersion = null)
 * @method Tag|null findOrException($id, $lockMode = null, $lockVersion = null)
 * @method Tag|null findOneBy(array $criteria, array $orderBy = null)
 * @method Tag findOneByOrException(array $criteria, array $orderBy = null)
 * @method Tag[]    findAll()
 * @method Tag[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TagRepository extends ServiceEntityRepository
{
    use FindByKeysTrait;
    use FindOrExceptionTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tag::class);
    }

    public function createDefaultQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('tag');
    }

    public function findWithUser(User $user): QueryBuilder
    {
        return $this->createDefaultQueryBuilder()
                    ->andWhere('tag.assignedTo = :user')
                    ->setParameter('user', $user)
        ;
    }

    public function findWithUserNameQueryBuilder(User $user, string $tagName): QueryBuilder
    {
        return $this->findWithUser($user)
                    ->andWhere('tag.name = :name')
                    ->setParameter('name', $tagName)
        ;
    }

    public function findWithUserName(User $user, string $tagName): Tag|null
    {
        return $this->findWithUserNameQueryBuilder($user, $tagName)
                    ->getQuery()
                    ->getOneOrNullResult()
        ;
    }

    public function exists(string $name): bool
    {
        $existingTag = $this->findOneBy(['name' => $name]);

        return !is_null($existingTag);
    }

    public function existsForUser(string $name, User $user): bool
    {
        $existingTag = $this->findOneBy(['name' => $name, 'assignedTo' => $user]);

        return !is_null($existingTag);
    }
}

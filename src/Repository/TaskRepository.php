<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Task;
use App\Entity\User;
use App\Form\Model\TaskListFilterModel;
use App\Traits\FindOrExceptionTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Task|null find($id, $lockMode = null, $lockVersion = null)
 * @method Task findOrException($id, $lockMode = null, $lockVersion = null)
 * @method Task|null findOneBy(array $criteria, array $orderBy = null)
 * @method Task findOneByOrException(array $criteria, array $orderBy = null)
 * @method Task[]    findAll()
 * @method Task[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TaskRepository extends ServiceEntityRepository
{
    use FindOrExceptionTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    public function createDefaultQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('task');
    }

    public function findByUserQueryBuilder(User $user): QueryBuilder
    {
        return $this->createDefaultQueryBuilder()
            ->andWhere('task.createdBy = :user')
            ->setParameter('user', $user)
        ;
    }

    public function applyNotCompleted(QueryBuilder $queryBuilder): QueryBuilder
    {
        return $queryBuilder->andWhere('task.completedAt IS NULL');
    }

    public function applyFilter(QueryBuilder $queryBuilder, TaskListFilterModel $filter): QueryBuilder
    {
        if ($filter->hasName()) {
            $name = strtolower($filter->getName());
            $queryBuilder->andWhere('LOWER(task.name) LIKE :name')
                ->setParameter('name', "%{$name}%")
            ;
        }

        if ($filter->hasDescription()) {
            $queryBuilder->andWhere('task.description LIKE :description')
                ->setParameter('description', "%{$filter->getDescription()}%")
            ;
        }

        if (!$filter->getShowCompleted()) {
            $queryBuilder->andWhere('task.completedAt IS NULL');
        }

        return $queryBuilder;
    }
}

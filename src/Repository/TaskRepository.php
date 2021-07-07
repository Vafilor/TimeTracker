<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Task;
use App\Entity\User;
use App\Form\Model\TaskListFilterModel;
use App\Traits\FindByKeysInterface;
use App\Traits\FindByKeysTrait;
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
 * @method Task[] findByKeys(string $key, mixed $values);
 */
class TaskRepository extends ServiceEntityRepository implements FindByKeysInterface
{
    use FindOrExceptionTrait;
    use FindByKeysTrait;

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
            ->andWhere('task.assignedTo = :user')
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
            $queryBuilder->andWhere('task.canonicalName LIKE :name')
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

    /**
     * @param string|Task $task the taskId or task entity.
     * @return int
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getTotalTimeInSeconds(string|Task $task): int
    {
        $queryBuilder = $this->createDefaultQueryBuilder()
                             ->join('task.timeEntries', 'time_entry')
                             ->select('total_seconds(time_entry.startedAt, time_entry.endedAt)')
                             ->andWhere('task = :task')
                             ->andWhere('time_entry.deletedAt IS NULL')
                             ->andWhere('time_entry.endedAt IS NOT NULL')
                             ->setParameter('task', $task)
                             ->getQuery()
        ;

        $result = $queryBuilder->getSingleScalarResult();
        if (is_null($result)) {
            return 0;
        }

        return intval($result);
    }

    public function findNotCompleted(User $user, string $name): ?Task
    {
        $queryBuilder = $this->createDefaultQueryBuilder()
                             ->andWhere('task.assignedTo = :user')
                             ->andWhere('task.name = :name')
                             ->andWhere('task.completedAt IS NULL')
                             ->setParameters([
                                 'user' => $user,
                                 'name' => $name
                             ])
        ;

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    public function preloadTags(?QueryBuilder $queryBuilder): QueryBuilder
    {
        if (is_null($queryBuilder)) {
            $queryBuilder = $this->createDefaultQueryBuilder();
        }

        $queryBuilder->addSelect('tag_link, tag')
            ->leftJoin('task.tagLinks', 'tag_link')
            ->leftJoin('tag_link.tag', 'tag')
        ;

        return $queryBuilder;
    }
}

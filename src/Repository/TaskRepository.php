<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Task;
use App\Entity\User;
use App\Form\Model\FilterTaskModel;
use App\Traits\FindByKeysInterface;
use App\Traits\FindByKeysTrait;
use App\Traits\FindOrExceptionTrait;
use DateTime;
use DateTimeZone;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Task|null find($id, $lockMode = null, $lockVersion = null)
 * @method Task      findOrException($id, $lockMode = null, $lockVersion = null)
 * @method Task|null findOneBy(array $criteria, array $orderBy = null)
 * @method Task      findOneByOrException(array $criteria, array $orderBy = null)
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

    public function createDefaultQueryBuilder(bool $includeDeleted = false): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('task');

        if (!$includeDeleted) {
            $queryBuilder = $queryBuilder->andWhere('task.deletedAt IS NULL');
        }

        return $queryBuilder;
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

    public function applyNotClosed(QueryBuilder $queryBuilder): QueryBuilder
    {
        return $queryBuilder->andWhere('task.closedAt IS NULL');
    }

    public function applyCompleted(QueryBuilder $queryBuilder): QueryBuilder
    {
        return $queryBuilder->andWhere('task.completedAt IS NOT NULL');
    }

    public function applyNoSubtasks(QueryBuilder $queryBuilder): QueryBuilder
    {
        return $queryBuilder->andWhere('task.parent IS NULL');
    }

    public function applyNullTimeEstimateSlowest(QueryBuilder $queryBuilder): QueryBuilder
    {
        return $queryBuilder->andWhere('task.completedAt IS NOT NULL');
    }

    /**
     * @throws \Exception
     */
    public function applyFilter(QueryBuilder $queryBuilder, FilterTaskModel $filter): QueryBuilder
    {
        if ($filter->hasContent()) {
            $canonicalContent = Task::canonicalizeName($filter->getContent());

            $queryBuilder->andWhere(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->like('task.description', ':content'),
                    $queryBuilder->expr()->like('task.canonicalName', ':canonicalContent')
                )
            );

            $queryBuilder->setParameter('content', "%{$filter->getContent()}%")
                         ->setParameter('canonicalContent', "%{$canonicalContent}%")
            ;
        }

        if (!$filter->getShowCompleted()) {
            $queryBuilder->andWhere('task.completedAt IS NULL');
        }

        if (!$filter->getShowClosed()) {
            $queryBuilder->andWhere('task.closedAt IS NULL');
        }

        if (!$filter->getShowSubtasks()) {
            $queryBuilder = $this->applyNoSubtasks($queryBuilder);
        }

        if ($filter->getOnlyShowPastDue()) {
            $now = new DateTime('now', new DateTimeZone('UTC'));
            $queryBuilder->andWhere('task.dueAt < :now')
                         ->setParameter('now', $now)
            ;
        }

        if ($filter->isOnlyTemplates()) {
            $queryBuilder->andWhere('task.template = :truthy')
                         ->setParameter('truthy', true)
            ;
        } else {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->eq('task.template', ':falsey'),
                    $queryBuilder->expr()->isNull('task.template')
                )
            )->setParameter('falsey', false);
        }

        if ($filter->hasTags()) {
            $tags = $filter->getTagsArray();
            $queryBuilder = $queryBuilder->andWhere('tag.name IN (:tags)')
                                         ->setParameter('tags', $tags)
            ;
        }

        if ($filter->hasParentTask()) {
            $queryBuilder = $queryBuilder->andWhere('task.parent = :parent')
                                         ->setParameter('parent', $filter->getParentTask())
            ;
        }

        return $queryBuilder;
    }

    /**
     * @param string|Task $task the taskId or task entity
     *
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
                                 'name' => $name,
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

    public function orderByTimeEstimate(QueryBuilder $queryBuilder, string $direction): QueryBuilder
    {
        $value = 'asc' === $direction ? PHP_INT_MAX : PHP_INT_MIN;

        return $queryBuilder->addSelect("CASE WHEN task.timeEstimate IS NULL THEN $value ELSE task.timeEstimate END as HIDDEN h_timeEstimate");
    }

    public function findActiveTasks(User $user): QueryBuilder
    {
        return $this->findByUserQueryBuilder($user)
            ->andWhere('task.active = :active')
            ->andWhere('task.completedAt IS NULL')
            ->andWhere('task.closedAt IS NULL')
            ->setParameter('active', true)
        ;
    }
}

<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\TimeEntry;
use App\Entity\User;
use App\Form\Model\TimeEntryListFilterModel;
use App\Traits\FindOrExceptionTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @method TimeEntry|null find($id, $lockMode = null, $lockVersion = null)
 * @method TimeEntry findOrException($id, $lockMode = null, $lockVersion = null)
 * @method TimeEntry|null findOneBy(array $criteria, array $orderBy = null)
 * @method TimeEntry findOneByOrException(array $criteria, array $orderBy = null)
 * @method TimeEntry[]    findAll()
 * @method TimeEntry[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TimeEntryRepository extends ServiceEntityRepository
{
    use FindOrExceptionTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TimeEntry::class);
    }

    public function createDefaultQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('time_entry')
                    ->andWhere('time_entry.deletedAt IS NULL');
    }

    public function findForTaskQueryBuilder(string $taskId): QueryBuilder
    {
        return $this->createDefaultQueryBuilder()
                    ->andWhere('time_entry.task = :task')
                    ->setParameter('task', $taskId)
        ;
    }

    public function preloadTags(?QueryBuilder $queryBuilder): QueryBuilder
    {
        if (is_null($queryBuilder)) {
            $queryBuilder = $this->createDefaultQueryBuilder();
        }

        $queryBuilder = $queryBuilder->addSelect('tag_link')
                                     ->leftJoin('time_entry.tagLinks', 'tag_link')
                                     ->leftJoin('tag_link.tag', 'tag')
        ;

        return $queryBuilder;
    }

    public function findWithTagFetch($id): TimeEntry|null
    {
        return $this->createDefaultQueryBuilder()
                             ->addSelect('tag_link, tag')
                             ->leftJoin('time_entry.tagLinks', 'tag_link')
                             ->leftJoin('tag_link.tag', 'tag')
                             ->andWhere('time_entry.id = :id')
                             ->setParameter('id', $id)
                             ->getQuery()
                             ->getOneOrNullResult()
        ;
    }

    public function findWithTagFetchOrException($id): TimeEntry
    {
        $result = $this->findWithTagFetch($id);

        if (is_null($result)) {
            throw new NotFoundHttpException();
        }

        return $result;
    }

    public function findByUserQueryBuilder(User $user): QueryBuilder
    {
        return $this->createDefaultQueryBuilder()
                    ->andWhere('time_entry.owner = :user')
                    ->setParameter('user', $user)
        ;
    }

    public function findRunningTimeEntry(User $user): ?TimeEntry
    {
        return $this->createDefaultQueryBuilder()
                    ->andWhere('time_entry.owner = :user')
                    ->andWhere('time_entry.endedAt IS NULL')
                    ->setParameter('user', $user)
                    ->getQuery()
                    ->getOneOrNullResult()
        ;
    }

    public function getLatestTimeEntry(User $user): ?TimeEntry
    {
        return $this->createDefaultQueryBuilder()
            ->andWhere('time_entry.owner = :user')
            ->orderBy('time_entry.createdAt', 'DESC')
            ->setParameter('user', $user)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function applyFilter(QueryBuilder $queryBuilder, TimeEntryListFilterModel $filter): QueryBuilder
    {
        if ($filter->hasStart()) {
            $queryBuilder = $queryBuilder
                ->andWhere('time_entry.startedAt >= :start')
                ->setParameter('start', $filter->getStart())
            ;
        }

        if ($filter->hasEnd()) {
            $queryBuilder = $queryBuilder
                ->andWhere('time_entry.endedAt <= :end')
                ->setParameter('end', $filter->getEnd())
            ;
        }

        if ($filter->hasTags()) {
            $tags = $filter->getTagsArray();
            $queryBuilder = $queryBuilder
                ->andWhere('tag.name IN (:tags)')
                ->setParameter('tags', $tags)
            ;
        }

        if ($filter->hasTask()) {
            $queryBuilder = $queryBuilder
                ->andWhere('time_entry.task = :taskId')
                ->setParameter('taskId', $filter->getTaskId())
            ;
        }

        return $queryBuilder;
    }
}

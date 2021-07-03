<?php

namespace App\Repository;

use App\Entity\Statistic;
use App\Entity\StatisticValue;
use App\Entity\TimeEntry;
use App\Entity\Timestamp;
use App\Entity\User;
use App\Traits\FindByKeysInterface;
use App\Traits\FindByKeysTrait;
use App\Traits\FindOrExceptionTrait;
use App\Util\DateRange;
use DateTimeZone;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method StatisticValue|null find($id, $lockMode = null, $lockVersion = null)
 * @method StatisticValue|null findOneBy(array $criteria, array $orderBy = null)
 * @method StatisticValue findOrException($id, $lockMode = null, $lockVersion = null)
 * @method StatisticValue findOneByOrException(array $criteria, array $orderBy = null)
 * @method StatisticValue[]    findAll()
 * @method StatisticValue[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StatisticValueRepository extends ServiceEntityRepository implements FindByKeysInterface
{
    use FindOrExceptionTrait;
    use FindByKeysTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StatisticValue::class);
    }

    public function createDefaultQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('statistic_value');
    }

    public function findWithUserQueryBuilder(User $user): QueryBuilder
    {
        return $this->createDefaultQueryBuilder()
                    ->addSelect('statistic')
                    ->join('statistic_value.statistic', 'statistic')
                    ->andWhere('statistic.assignedTo = :user')
                    ->setParameter('user', $user)
        ;
    }

    public function findWithUser(User $user)
    {
        return $this->findWithUserQueryBuilder($user)
                    ->getQuery()
                    ->getResult()
        ;
    }

    public function findForStatisticResource(Statistic $statistic, TimeEntry|Timestamp $resource): ?StatisticValue
    {
        $queryBuilder = $this->createDefaultQueryBuilder()
                             ->join('statistic_value.statistic', 'statistic')
                             ->andWhere('statistic.name = :name')
                             ->setParameter('name', $statistic->getName())
        ;

        if ($resource instanceof TimeEntry) {
            $queryBuilder = $queryBuilder->join('statistic_value.timeEntry', 'timeEntry')
                                         ->andWhere('timeEntry = :resource')
                                         ->setParameter('resource', $resource)
            ;
        } elseif ($resource  instanceof Timestamp) {
            $queryBuilder = $queryBuilder->join('statistic_value.timestamp', 'timestamp')
                                         ->andWhere('timestamp = :resource')
                                         ->setParameter('resource', $resource)
            ;
        }

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * @param Statistic $statistic
     * @param DateRange $dateRange
     * @return StatisticValue|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findForDay(Statistic $statistic, DateRange $dateRange): ?StatisticValue
    {
        $start = $dateRange->getStart()->setTimezone(new DateTimeZone('UTC'));
        $end = $dateRange->getEnd()->setTimezone(new DateTimeZone('UTC'));

        return $this->createDefaultQueryBuilder()
                    ->andWhere('statistic_value.statistic = :statistic')
                    ->andWhere('statistic_value.timestamp IS NULL')
                    ->andWhere('statistic_value.timeEntry IS NULL')
                    ->andWhere('statistic_value.startedAt = :start')
                    ->andWhere('statistic_value.endedAt = :end')
                    ->setParameters([
                        'statistic' => $statistic,
                        'start' => $start,
                        'end' => $end
                     ])
                    ->getQuery()
                    ->getOneOrNullResult()
        ;
    }
}

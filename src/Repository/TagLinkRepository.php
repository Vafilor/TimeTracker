<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Note;
use App\Entity\Statistic;
use App\Entity\Tag;
use App\Entity\TagLink;
use App\Entity\Task;
use App\Entity\TimeEntry;
use App\Entity\Timestamp;
use App\Traits\FindOrExceptionTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @method TagLink|null find($id, $lockMode = null, $lockVersion = null)
 * @method TagLink      findOrException($id, $lockMode = null, $lockVersion = null)
 * @method TagLink|null findOneBy(array $criteria, array $orderBy = null)
 * @method TagLink      findOneByOrException(array $criteria, array $orderBy = null)
 * @method TagLink[]    findAll()
 * @method TagLink[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TagLinkRepository extends ServiceEntityRepository
{
    use FindOrExceptionTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TagLink::class);
    }

    public function createDefaultQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('tag_link');
    }

    /**
     * Finds the TagLinks for a TimeEntry with the tags fetched.
     *
     * @return TagLink[]
     */
    public function findForTimeEntry(TimeEntry $timeEntry): array
    {
        return $this->createDefaultQueryBuilder()
                    ->addSelect('tag')
                    ->join('tag_link.tag', 'tag')
                    ->andWhere('tag_link.timeEntry = :timeEntry')
                    ->setParameter('timeEntry', $timeEntry)
                    ->getQuery()
                    ->getResult()
        ;
    }

    public function findForResource(TimeEntry|Timestamp|Task|Statistic|Note $resource, Tag $tag): ?TagLink
    {
        $data = ['tag' => $tag];

        if ($resource instanceof TimeEntry) {
            $data['timeEntry'] = $resource;
        } elseif ($resource instanceof Timestamp) {
            $data['timestamp'] = $resource;
        } elseif ($resource instanceof Task) {
            $data['task'] = $resource;
        } elseif ($resource instanceof Statistic) {
            $data['statistic'] = $resource;
        } elseif ($resource instanceof Note) {
            $data['note'] = $resource;
        }

        return $this->findOneBy($data);
    }

    public function findForResourceOrException(TimeEntry|Timestamp|Task|Statistic|Note $resource, Tag $tag): TagLink
    {
        $result = $this->findForResource($resource, $tag);

        if (is_null($result)) {
            throw new NotFoundHttpException();
        }

        return $result;
    }
}

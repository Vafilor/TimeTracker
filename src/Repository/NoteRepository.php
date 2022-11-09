<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Note;
use App\Entity\User;
use App\Form\Model\FilterNoteModel;
use App\Traits\FindByKeysInterface;
use App\Traits\FindByKeysTrait;
use App\Traits\FindOrExceptionTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Note|null find($id, $lockMode = null, $lockVersion = null)
 * @method Note|null findOneBy(array $criteria, array $orderBy = null)
 * @method Note[]    findAll()
 * @method Note[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method Note      findOrException($id, $lockMode = null, $lockVersion = null)
 * @method Note      findOneByOrException(array $criteria, array $orderBy = null)
 */
class NoteRepository extends ServiceEntityRepository implements FindByKeysInterface
{
    use FindOrExceptionTrait;

    use FindByKeysTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Note::class);
    }

    public function createDefaultQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('note');
    }

    public function findByUserQueryBuilder(User $user): QueryBuilder
    {
        return $this->createDefaultQueryBuilder()
                    ->andWhere('note.assignedTo = :user')
                    ->setParameter('user', $user)
        ;
    }

    public function preloadTags(?QueryBuilder $queryBuilder): QueryBuilder
    {
        if (is_null($queryBuilder)) {
            $queryBuilder = $this->createDefaultQueryBuilder();
        }

        $queryBuilder = $queryBuilder->addSelect('tag_link, tag')
            ->leftJoin('note.tagLinks', 'tag_link')
            ->leftJoin('tag_link.tag', 'tag')
        ;

        return $queryBuilder;
    }

    public function applyFilter(QueryBuilder $queryBuilder, FilterNoteModel $filter): QueryBuilder
    {
        if ($filter->hasContent()) {
            $content = $filter->getContent();
            $queryBuilder = $queryBuilder->andWhere(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->like('note.title', ':content'),
                    $queryBuilder->expr()->like('note.content', ':content')
                )
            )
            ->setParameter('content', "%{$content}%");
        }

        if ($filter->hasTags()) {
            $tags = $filter->getTagsArray();
            $queryBuilder = $queryBuilder
                ->andWhere('tag.name IN (:tags)')
                ->setParameter('tags', $tags)
            ;
        }

        return $queryBuilder;
    }
}

<?php

declare(strict_types=1);

namespace App\Traits;

use Doctrine\ORM\QueryBuilder;

trait FindByKeysTrait
{
    public function findByKeysQuery(string $key, array $values, string $alias = 'a'): QueryBuilder
    {
        return $this->createQueryBuilder($alias)
                    ->andWhere("{$alias}.{$key} in (:values)")
                    ->setParameter('values', $values)
        ;
    }

    public function findByKeys(string $key, $values)
    {
        return $this->findByKeysQuery($key, $values)
                    ->getQuery()
                    ->getResult()
        ;
    }

    public function findOneByKey(string $key, $value)
    {
        return $this->findOneBy([$key => $value]);
    }

    public function findExcludingQuery(string $key, array $values, string $alias = 'a'): QueryBuilder
    {
        return $this->createQueryBuilder($alias)
                    ->andWhere("{$alias}.{$key} not in (:values)")
                    ->setParameter('values', $values)
        ;
    }

    public function findExcluding(string $key, array $values)
    {
        return $this->findExcludingQuery($key, $values)
                    ->getQuery()
                    ->getResult()
        ;
    }
}

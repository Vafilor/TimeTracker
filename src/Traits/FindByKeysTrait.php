<?php

declare(strict_types=1);

namespace App\Traits;

use Doctrine\ORM\QueryBuilder;

trait FindByKeysTrait
{
    public function findByKeysQuery(string $key, array $keys, string $alias = 'a'): QueryBuilder
    {
        return $this->createQueryBuilder($alias)
                    ->andWhere("{$alias}.{$key} in (:keys)")
                    ->setParameter('keys', $keys)
        ;
    }

    public function findByKeys(string $key, $keys)
    {
        return $this->findByKeysQuery($key, $keys)
                    ->getQuery()
                    ->getResult()
        ;
    }

    public function findExcludingQuery(string $key, array $keys, string $alias = 'a'): QueryBuilder
    {
        return $this->createQueryBuilder($alias)
                    ->andWhere("{$alias}.{$key} not in (:keys)")
                    ->setParameter('keys', $keys)
        ;
    }

    public function findExcluding(string $key, array $keys)
    {
        return $this->findExcludingQuery($key, $keys)
                    ->getQuery()
                    ->getResult()
        ;
    }
}

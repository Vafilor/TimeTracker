<?php

namespace App\Traits;

use Doctrine\ORM\QueryBuilder;

interface FindByKeysInterface
{
    public function findByKeysQuery(string $key, array $values, string $alias = 'a'): QueryBuilder;
    public function findByKeys(string $key, $values);
    public function findOneByKey(string $key, $value);
    public function findExcludingQuery(string $key, array $values, string $alias = 'a'): QueryBuilder;
    public function findExcluding(string $key, array $values);
}
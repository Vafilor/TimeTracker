<?php

namespace App\Transfer;

use App\Traits\FindByKeysInterface;
use App\Util\Collections;
use App\Util\TypeUtil;
use RuntimeException;

class RepositoryKeyCache
{
    private FindByKeysInterface $repository;

    private array $keyToObject;

    public function __construct(FindByKeysInterface $repository)
    {
        $this->repository = $repository;
        $this->keyToObject = [];
    }

    public function findOneByKey(string $key, string $value)
    {
        if ($this->hasKey($key, $value)) {
            return $this->keyToObject[$key][$value];
        }

        return null;
    }

    public function hasKey(string $key, string $value): bool
    {
        return array_key_exists($key, $this->keyToObject) &&
               array_key_exists($value, $this->keyToObject[$key]);
    }

    public function findOneByKeyOrException(string $key, $value)
    {
        $item = $this->findOneByKey($key, $value);
        if (is_null($item)) {
            $className = TypeUtil::getClassName($this->repository);
            throw new RuntimeException("$className did not find entity with key '$key' for value '$value'");
        }

        return $item;
    }

    public function loadByKey(string $key, array $values): static
    {
        $items = $this->repository->findByKeys($key, $values);

        $this->keyToObject = [];

        $this->keyToObject = [
            $key => Collections::mapByKeyUnique($items, $key),
        ];

        return $this;
    }

    public function loadByIds(array $ids): static
    {
        $items = $this->repository->findByKeys('id', $ids);

        $this->keyToObject = [];

        $this->keyToObject = [
            'id' => Collections::mapByKeyUnique($items, 'idString'),
        ];

        return $this;
    }

    public function findById(string $id)
    {
        return $this->findOneByKey('id', $id);
    }

    public function findByIdOrException(string $id)
    {
        $item = $this->findById($id);

        if (is_null($item)) {
            $className = TypeUtil::getClassName($this->repository);
            throw new RuntimeException("$className did not find entity with id '$id'");
        }

        return $item;
    }
}

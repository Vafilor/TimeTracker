<?php

declare(strict_types=1);

namespace App\Util;

use Countable;
use Exception;
use InvalidArgumentException;
use LogicException;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class Collections
{
    /**
     * For each item, gets the $key value from the item and returns an array of the keys.
     *
     * Note that the array might contain duplicates.
     *
     * @param $items
     * @param $key
     *
     * @return array
     */
    public static function pluck(iterable $items, string $key): array
    {
        $propertyAccessor = new PropertyAccessor();

        $pluckedItems = [];

        foreach ($items as $item) {
            $pluckedItems[] = $propertyAccessor->getValue($item, $key);
        }

        return $pluckedItems;
    }

    /**
     * For each item, gets the $key value from the item and returns an array of the keys.
     *
     * @param $items
     * @param $key
     *
     * @return array
     */
    public static function pluckNoDuplicates(iterable $items, string $key): array
    {
        $propertyAccessor = new PropertyAccessor();

        $pluckedItems = [];
        $properties = [];

        foreach ($items as $item) {
            $value = $propertyAccessor->getValue($item, $key);

            if (array_key_exists($value, $properties)) {
                continue;
            }

            $properties[$value] = true;
            $pluckedItems[] = $value;
        }

        return $pluckedItems;
    }

    /**
     * Returns an associative array where each key points to an item.
     * The key is the property value of item returned by $key.
     *
     * If you have an array of objects with uuids, you can then create an associative array
     * where each key is the uuid and it maps to the object.
     *
     * This method assumes the key will be unique.
     *
     * @param $items
     * @param $key
     *
     * @return array
     *
     * @throws LogicException if the keys are not unique
     */
    public static function mapByKeyUnique(iterable $items, string $key)
    {
        $result = [];

        $propertyAccessor = new PropertyAccessor();

        foreach ($items as $item) {
            $itemKey = $propertyAccessor->getValue($item, $key);

            if (array_key_exists($itemKey, $result)) {
                throw new LogicException("Key $itemKey already in result. Property path $key");
            }

            $result[$itemKey] = $item;
        }

        return $result;
    }

    /**
     * Find an item in a collection, comparing by id.
     * If it exists, it is returned. Otherwise null is returned.
     *
     * @param $items
     *
     * @return mixed|null
     */
    public static function findByUuid(UuidInterface $id, iterable $items)
    {
        foreach ($items as $item) {
            if ($item->getId()->equals($id)) {
                return $item;
            }
        }

        return null;
    }

    /**
     * @param $items
     *
     * @return mixed
     *
     * @throws Exception
     */
    public static function pickRandom(Countable|array $items)
    {
        if (0 === count($items)) {
            throw new InvalidArgumentException('Items has no elements. Can not pick random');
        }

        if (1 === count($items)) {
            return $items[0];
        }

        $randomIndex = random_int(0, count($items) - 1);

        return $items[$randomIndex];
    }
}

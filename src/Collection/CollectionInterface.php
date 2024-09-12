<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Collection;

use CultuurNet\UDB3\Collection\Exception\CollectionKeyNotFoundException;
use CultuurNet\UDB3\Collection\Exception\CollectionItemNotFoundException;

/**
 * @deprecated
 *   Use CultuurNet\UDB3\Model\ValueObject\Collection instead where possible.
 */
interface CollectionInterface extends \IteratorAggregate
{
    /**
     * @param mixed $item
     *   Item to add to the Collection.
     *
     * @return static
     *   Copy of the Collection, with the new item.
     *
     * @throws \InvalidArgumentException
     *   When the provided item is of an incorrect type.
     */
    public function with($item);

    /**
     * @param string|int $key
     *   Key to use for the new item. If not provided, the item will be added
     *   to the end of the Collection.
     * @param mixed $item
     *   Item to add to the Collection.
     *
     * @return static
     *   Copy of the Collection, with the new item.
     *
     * @throws \InvalidArgumentException
     *   When the provided item is of an incorrect type.
     */
    public function withKey($key, $item);

    /**
     * @param mixed $item
     *   Item to remove from the Collection.
     *
     * @return static
     *   Copy of the Collection, without the specified item.
     *
     * @throws \InvalidArgumentException
     *   When the provided item is of an incorrect type.
     *
     * @throws CollectionItemNotFoundException
     *   When the provided item is not present in the Collection.
     */
    public function without($item);

    /**
     * @param string $key
     *   Key to remove from the Collection.
     *
     * @return static
     *   Copy of the Collection, without the specified key.
     *
     * @throws CollectionKeyNotFoundException
     *   When the provided key is not present in the Collection.
     */
    public function withoutKey(string $key);

    /**
     * @param mixed $item
     *   Item to check if it's present in the collection.
     *
     * @throws \InvalidArgumentException
     *   When the provided item is of an incorrect type.
     */
    public function contains($item): bool;

    /**
     * @param string $key
     *   Key to find the corresponding item for.
     *
     * @return mixed
     *   Item corresponding to the provided key.
     *
     * @throws CollectionKeyNotFoundException
     *   When the provided key is not present in the Collection.
     */
    public function getByKey(string $key);

    /**
     * @param mixed $item
     *   Item to get the key for.
     *
     * @return string|int
     *   Key corresponding to the provided item.
     *
     * @throws \InvalidArgumentException
     *   When the provided item is of an incorrect type.
     *
     * @throws CollectionItemNotFoundException
     *   When the provided item is not present in the Collection.
     */
    public function getKeyFor($item);

    /**
     * @return array
     *   List of all keys in the Collection.
     */
    public function getKeys(): array;

    /**
     * @return int
     *   Number of items in the Collection.
     */
    public function length(): int;

    /**
     * @return array
     *   Array of items in the Collection.
     */
    public function toArray(): array;

    /**
     * @param array $items
     *   Array of items to populate the Collection with.
     *
     * @return static
     *   A new Collection instance with the provided items.
     *
     * @throws \InvalidArgumentException
     *   When one of the provided items is of an incorrect type.
     */
    public static function fromArray(array $items);
}

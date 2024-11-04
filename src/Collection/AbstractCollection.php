<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Collection;

use CultuurNet\UDB3\Collection\Exception\CollectionItemNotFoundException;
use CultuurNet\UDB3\Collection\Exception\CollectionKeyNotFoundException;

/**
 * @deprecated
 *   Use CultuurNet\UDB3\Model\ValueObject\Collection instead where possible.
 */
abstract class AbstractCollection implements CollectionInterface
{
    protected array $items;

    final public function __construct()
    {
        $this->items = [];
    }

    /**
     * @inheritdoc
     */
    public function with($item)
    {
        $this->guardObjectType($item);

        $copy = clone $this;
        $copy->items[] = $item;
        return $copy;
    }

    /**
     * @inheritdoc
     */
    public function withKey($key, $item)
    {
        $this->guardObjectType($item);

        $copy = clone $this;
        $copy->items[$key] = $item;
        return $copy;
    }

    /**
     * @inheritdoc
     */
    public function without($item)
    {
        $key = $this->getKeyFor($item);

        $copy = clone $this;
        unset($copy->items[$key]);
        return $copy;
    }

    /**
     * @inheritdoc
     */
    public function withoutKey(string $key)
    {
        if (!isset($this->items[$key])) {
            throw new CollectionKeyNotFoundException($key);
        }

        $copy = clone $this;
        unset($copy->items[$key]);
        return $copy;
    }

    public function contains($item): bool
    {
        $this->guardObjectType($item);

        $filtered = array_filter(
            $this->items,
            function ($itemToCompare) use ($item) {
                return ($item == $itemToCompare);
            }
        );

        return !empty($filtered);
    }

    /**
     * @inheritdoc
     */
    public function getByKey(string $key)
    {
        if (!isset($this->items[$key])) {
            throw new CollectionKeyNotFoundException($key);
        }

        return $this->items[$key];
    }

    public function getKeyFor($item)
    {
        $this->guardObjectType($item);

        $key = array_search($item, $this->items);

        if ($key === false) {
            throw new CollectionItemNotFoundException();
        }

        return $key;
    }

    public function getKeys(): array
    {
        return array_keys($this->items);
    }

    public function length(): int
    {
        return count($this->items);
    }

    public function toArray(): array
    {
        return $this->items;
    }

    /**
     * @inheritdoc
     */
    public static function fromArray(array $items)
    {
        $collection = new static();
        foreach ($items as $item) {
            $collection = $collection->with($item);
        }
        return $collection;
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }

    abstract protected function getValidObjectType(): string;

    /**
     * @param mixed $object
     *   Object of which the type should be validated.
     *
     * @return bool
     *   TRUE if the object is of a valid type, FALSE otherwise.
     */
    protected function isValidObjectType($object): bool
    {
        $type = $this->getValidObjectType();
        return ($object instanceof $type);
    }

    /**
     * @param mixed $object
     *   Object of which the type should be guarded.
     *
     * @throws \InvalidArgumentException
     *   When the provided object is not of the specified type.
     */
    protected function guardObjectType($object): void
    {
        if (!$this->isValidObjectType($object)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Expected instance of %s, found %s instead.',
                    $this->getValidObjectType(),
                    get_class($object)
                )
            );
        }
    }
}

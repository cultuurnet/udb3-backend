<?php

namespace CultuurNet\UDB3\Model\ValueObject\Collection;

abstract class Collection implements \IteratorAggregate, \Countable
{
    /**
     * @var array
     */
    private $values;

    /**
     * @param array ...$values
     */
    public function __construct(...$values)
    {
        array_walk(
            $values,
            function ($value, $key) {
                if (!is_object($value)) {
                    throw new \InvalidArgumentException("Value for key {$key} is not an object.");
                }
            }
        );

        $this->values = $values;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->values;
    }

    /**
     * @param mixed $value
     * @return static
     */
    public function with($value)
    {
        $values = $this->values;
        $values[] = $value;
        /** @phpstan-ignore-next-line */
        return new static(...$values);
    }

    /**
     * @param callable $callback
     * @return static
     * @see array_filter
     */
    public function filter(callable $callback)
    {
        $values = array_filter($this->values, $callback);
        /** @phpstan-ignore-next-line */
        return new static(...$values);
    }

    /**
     * @param mixed $value
     * @return bool
     * @see array_search
     */
    public function contains($value)
    {
        $index = array_search($value, $this->values);
        return is_int($index);
    }

    /**
     * @return int
     * @see count
     */
    public function getLength()
    {
        return count($this->values);
    }

    /**
     * @return bool
     * @see empty
     */
    public function isEmpty()
    {
        return empty($this->values);
    }

    /**
     * @param int $index
     * @return mixed|null
     */
    public function getByIndex($index)
    {
        if (!isset($this->values[$index])) {
            throw new \OutOfBoundsException("No value exists at index {$index}.");
        }

        return $this->values[$index];
    }

    /**
     * @return mixed|null
     */
    public function getFirst()
    {
        if ($this->getLength() > 0) {
            return $this->getByIndex(0);
        }
        return null;
    }

    /**
     * @return mixed|null
     */
    public function getLast()
    {
        if ($this->getLength() > 0) {
            return $this->getByIndex($this->getLength() - 1);
        }
        return null;
    }

    /**
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->values);
    }

    /**
     * @return int
     */
    public function count()
    {
        return $this->getLength();
    }

    /**
     * @param array $values
     * @return static
     */
    public static function fromArray(array $values)
    {
        /** @phpstan-ignore-next-line */
        return new static(...$values);
    }
}

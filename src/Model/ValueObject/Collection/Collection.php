<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Collection;

abstract class Collection implements \IteratorAggregate, \Countable
{
    private array $values;

    /**
     * @param mixed ...$values
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

    public function toArray(): array
    {
        return $this->values;
    }

    /**
     * @return static
     */
    public function with($value) // @phpstan-ignore-line III-5812 Can be fixed once updating to PHP 8 => static
    {
        $values = $this->values;
        $values[] = $value;
        /** @phpstan-ignore-next-line */
        return new static(...$values);
    }

    /**
     * @return static
     */
    public function without($value) // @phpstan-ignore-line
    {
        $values = $this->values;
        $index = array_search($value, $values);

        if (is_int($index)) {
            unset($values[$index]);
        }
        /** @phpstan-ignore-next-line */
        return new static(...$values);
    }

    /**
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
     * @see array_search
     */
    public function contains($value): bool // @phpstan-ignore-line
    {
        $index = array_search($value, $this->values);
        return is_int($index);
    }

    /**
     * @see count
     */
    public function getLength(): int
    {
        return count($this->values);
    }

    /**
     * @see empty
     */
    public function isEmpty(): bool
    {
        return empty($this->values);
    }

    /**
     * @return mixed|null
     */
    public function getByIndex(int $index)
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

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->values);
    }

    public function count(): int
    {
        return $this->getLength();
    }

    /**
     * @return static
     */
    public static function fromArray(array $values)
    {
        /** @phpstan-ignore-next-line */
        return new static(...$values);
    }
}

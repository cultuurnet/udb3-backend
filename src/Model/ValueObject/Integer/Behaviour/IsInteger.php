<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Integer\Behaviour;

trait IsInteger
{
    private int $value;

    public function toInteger(): int
    {
        return $this->value;
    }

    /**
     * @param IsInteger|mixed $other
     */
    public function sameAs($other): bool
    {
        return get_class($this) === get_class($other) && $this->toInteger() === $other->toInteger();
    }

    /**
     * @param IsInteger|mixed $other
     */
    public function equals($other): bool
    {
        $this->guardSameType($other);
        return $this->toInteger() === $other->toInteger();
    }

    /**
     * @param IsInteger|mixed $other
     */
    public function lt($other): bool
    {
        $this->guardSameType($other);
        return $this->toInteger() < $other->toInteger();
    }

    /**
     * @param IsInteger|mixed $other
     */
    public function lte($other): bool
    {
        $this->guardSameType($other);
        return $this->toInteger() <= $other->toInteger();
    }

    /**
     * @param IsInteger|mixed $other
     */
    public function gt($other): bool
    {
        $this->guardSameType($other);
        return $this->toInteger() > $other->toInteger();
    }

    /**
     * @param IsInteger|mixed $other
     */
    public function gte($other): bool
    {
        $this->guardSameType($other);
        return $this->toInteger() >= $other->toInteger();
    }

    /**
     * @throws \InvalidArgumentException
     */
    private function guardSameType($other): void
    {
        $thisClass = get_class($this);
        $otherClass = get_class($other);

        if ($thisClass !== $otherClass) {
            throw new \InvalidArgumentException("Expected ${thisClass}, got ${otherClass} instead.");
        }
    }

    /**
     * @throws \InvalidArgumentException
     */
    private function guardInteger($value): void
    {
        if (!is_int($value)) {
            throw new \InvalidArgumentException('Given value should be an int, got ' . gettype($value) . ' instead.');
        }
    }

    private function setValue($value): void
    {
        $this->guardInteger($value);
        $this->value = $value;
    }
}

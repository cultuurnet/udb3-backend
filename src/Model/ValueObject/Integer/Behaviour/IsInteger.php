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
     * @param IsInteger|mixed $other
     */
    private function guardSameType($other): void
    {
        $thisClass = get_class($this);
        $otherClass = get_class($other);

        if ($thisClass !== $otherClass) {
            throw new \InvalidArgumentException("Expected ${thisClass}, got ${otherClass} instead.");
        }
    }

    private function setValue(int $value): void
    {
        $this->value = $value;
    }
}

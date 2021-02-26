<?php

namespace CultuurNet\UDB3\Model\ValueObject\Integer\Behaviour;

trait IsInteger
{
    /**
     * @var int
     */
    private $value;

    /**
     * @return int
     */
    public function toInteger()
    {
        return $this->value;
    }

    /**
     * @param IsInteger|mixed $other
     * @return bool
     */
    public function sameAs($other)
    {
        return get_class($this) === get_class($other) && $this->toInteger() === $other->toInteger();
    }

    /**
     * @param IsInteger|mixed $other
     * @return bool
     */
    public function equals($other)
    {
        $this->guardSameType($other);
        return $this->toInteger() === $other->toInteger();
    }

    /**
     * @param IsInteger|mixed $other
     * @return bool
     */
    public function lt($other)
    {
        $this->guardSameType($other);
        return $this->toInteger() < $other->toInteger();
    }

    /**
     * @param IsInteger|mixed $other
     * @return bool
     */
    public function lte($other)
    {
        $this->guardSameType($other);
        return $this->toInteger() <= $other->toInteger();
    }

    /**
     * @param IsInteger|mixed $other
     * @return bool
     */
    public function gt($other)
    {
        $this->guardSameType($other);
        return $this->toInteger() > $other->toInteger();
    }

    /**
     * @param IsInteger|mixed $other
     * @return bool
     */
    public function gte($other)
    {
        $this->guardSameType($other);
        return $this->toInteger() >= $other->toInteger();
    }

    /**
     * @throws \InvalidArgumentException
     */
    private function guardSameType($other)
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
    private function guardInteger($value)
    {
        if (!is_int($value)) {
            throw new \InvalidArgumentException('Given value should be an int, got ' . gettype($value) . ' instead.');
        }
    }

    /**
     * @param int $value
     */
    private function setValue($value)
    {
        $this->guardInteger($value);
        $this->value = $value;
    }
}

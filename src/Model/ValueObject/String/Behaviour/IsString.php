<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\String\Behaviour;

trait IsString
{
    private string $value;

    public function toString(): string
    {
        return $this->value;
    }

    /**
     * @param IsString|mixed $other
     */
    public function sameAs($other): bool
    {
        /* @var IsString $other */
        return get_class($this) === get_class($other) &&
            $this->toString() === $other->toString();
    }

    /**
     * @throws \InvalidArgumentException
     */
    private function guardString($value): void
    {
        if (!is_string($value)) {
            throw new \InvalidArgumentException('Given value should be a string, got ' . gettype($value) . ' instead.');
        }
    }

    /**
     * @param string $value
     */
    private function setValue($value): void
    {
        $this->guardString($value);
        $this->value = $value;
    }
}

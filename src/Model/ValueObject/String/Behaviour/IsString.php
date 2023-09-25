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

    private function setValue(string $value): void
    {
        $this->value = $value;
    }

    public function isEmpty(): bool
    {
        return $this->value === '';
    }
}

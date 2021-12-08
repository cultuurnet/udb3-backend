<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Collection\Behaviour;

trait IsStringArray
{
    /**
     * @return string[]
     */
    public function toStringArray(): array
    {
        return array_map(
            fn (object $value) => $value->toString(),
            $this->toArray()
        );
    }

    public function sameAs($other): bool
    {
        return $this->toStringArray() === $other->toStringArray();
    }
}

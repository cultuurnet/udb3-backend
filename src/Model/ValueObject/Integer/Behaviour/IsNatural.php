<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Integer\Behaviour;

trait IsNatural
{
    public function guardNatural(int $value): void
    {
        if ($value < 0) {
            throw new \InvalidArgumentException(
                "Given integer should be greater or equal to zero. Got {$value} instead."
            );
        }
    }
}

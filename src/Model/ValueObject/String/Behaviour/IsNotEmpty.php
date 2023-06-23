<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\String\Behaviour;

trait IsNotEmpty
{
    private function guardNotEmpty(string $value): void
    {
        if (strlen($value) === 0) {
            throw new \InvalidArgumentException('Given string should not be empty.');
        }
    }
}

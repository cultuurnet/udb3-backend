<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\String\Behaviour;

use InvalidArgumentException;

trait HasMaxLength
{
    private function guardTooLong(string $value, int $maxLength, string $path='/'): void
    {
        if (mb_strlen($value) > $maxLength) {
            throw new InvalidArgumentException(sprintf('Given string should not be longer than %d characters.', $maxLength));
        }
    }
}

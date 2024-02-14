<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\String\Behaviour;

use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\Exception\StringIsInvalid;

trait HasMaxLength
{
    private function guardTooLong(string $fieldName, string $value, int $maxLength): void
    {
        if (mb_strlen($value) > $maxLength) {
            throw StringIsInvalid::becauseMaxLengthIsExceeded($fieldName, $maxLength);
        }
    }
}

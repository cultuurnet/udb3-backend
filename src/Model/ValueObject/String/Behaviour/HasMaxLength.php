<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\String\Behaviour;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;

trait HasMaxLength
{
    private function hasMaxLength(string $value, int $maxLength, string $path='/'): void
    {
        if (mb_strlen($value) > $maxLength) {
            throw ApiProblem::bodyInvalidData(
                new SchemaError($path, sprintf('Given string should not be longer than %d characters.', $maxLength)),
            );
        }
    }
}

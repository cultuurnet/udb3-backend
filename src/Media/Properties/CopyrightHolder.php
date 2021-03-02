<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media\Properties;

use InvalidArgumentException;
use ValueObjects\Exception\InvalidNativeArgumentException;
use ValueObjects\StringLiteral\StringLiteral;

final class CopyrightHolder extends StringLiteral
{
    public function __construct($value)
    {
        if (false === \is_string($value)) {
            throw new InvalidNativeArgumentException($value, ['string']);
        }

        if (strlen($value) < 2) {
            throw new InvalidArgumentException('The name of a copyright holder should be at least 2 characters');
        }

        parent::__construct(substr($value, 0, 250));
    }
}

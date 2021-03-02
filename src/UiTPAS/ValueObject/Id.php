<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPAS\ValueObject;

use ValueObjects\StringLiteral\StringLiteral;

class Id extends StringLiteral
{
    public function __construct($value)
    {
        parent::__construct($value);

        $value = trim($value);

        if (strlen($value) === 0) {
            throw new \InvalidArgumentException('ID should not be an empty string.');
        }
    }
}

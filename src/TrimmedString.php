<?php

namespace CultuurNet\UDB3;

use ValueObjects\StringLiteral\StringLiteral;

abstract class TrimmedString extends StringLiteral
{
    public function __construct($value)
    {
        if (is_string($value)) {
            $value = trim($value);
        }

        parent::__construct($value);
    }
}

<?php

namespace CultuurNet\UDB3\Role\ValueObjects;

use ValueObjects\StringLiteral\StringLiteral;

class Query extends StringLiteral
{
    /**
     * @param string $value
     */
    public function __construct(string $value)
    {
        if (empty($value)) {
            throw new \InvalidArgumentException('Query can\'t be empty.');
        }
        parent::__construct($value);
    }
}

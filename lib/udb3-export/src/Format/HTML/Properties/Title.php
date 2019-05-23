<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\EventExport\Format\HTML\Properties;

use ValueObjects\StringLiteral\StringLiteral;

class Title extends StringLiteral
{
    public function __construct($value)
    {
        parent::__construct($value);

        if ($this->isEmpty()) {
            throw new \InvalidArgumentException(
                'title can not be empty'
            );
        }
    }
}

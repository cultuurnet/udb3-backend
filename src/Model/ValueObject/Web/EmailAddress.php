<?php

namespace CultuurNet\UDB3\Model\ValueObject\Web;

use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\IsString;

class EmailAddress
{
    use IsString;

    /**
     * @param string $value
     */
    public function __construct($value)
    {
        $this->guardString($value);

        if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            throw new \InvalidArgumentException('Given string is not a valid e-mail address.');
        }

        $this->setValue($value);
    }
}

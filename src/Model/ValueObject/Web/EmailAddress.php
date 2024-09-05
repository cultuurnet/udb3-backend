<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Web;

use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\IsString;

class EmailAddress
{
    use IsString;

    public function __construct(string $value)
    {
        if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidEmailAddress('Given string is not a valid e-mail address.');
        }

        $this->setValue($value);
    }
}

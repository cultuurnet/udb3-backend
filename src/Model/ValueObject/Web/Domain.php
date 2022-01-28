<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Web;

use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\IsString;

abstract class Domain
{
    use IsString;

    public function __construct(string $value)
    {
        $this->guardString($value);

        if ((filter_var($value, FILTER_VALIDATE_DOMAIN) || filter_var($value, FILTER_VALIDATE_IP)) === false) {
            throw new \InvalidArgumentException('Given string is not a valid domain.');
        }
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Web;

use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\IsString;

final class Hostname
{
    use IsString;

    public function __construct(string $value)
    {
        if (filter_var($value, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false) {
            throw new \InvalidArgumentException('Given string is not a valid hostname.');
        }

        $this->setValue($value);
    }
}

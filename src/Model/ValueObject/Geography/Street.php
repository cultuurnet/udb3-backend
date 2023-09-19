<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Geography;

use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\IsString;

class Street
{
    use IsString;

    public function __construct(string $value)
    {
        $this->setValue($value);
    }
}

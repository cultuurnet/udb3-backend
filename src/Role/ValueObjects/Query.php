<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\ValueObjects;

use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\IsString;

class Query
{
    use IsString;

    public function __construct(string $value)
    {
        if (empty($value)) {
            throw new \InvalidArgumentException('Query can\'t be empty.');
        }

        $this->value = $value;
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Geography;

use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\IsNotEmpty;
use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\IsString;

class Locality
{
    use IsString;
    use IsNotEmpty;

    public function __construct(string $value)
    {
        $this->guardNotEmpty($value);
        $this->setValue($value);
    }
}

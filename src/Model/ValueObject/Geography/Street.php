<?php

namespace CultuurNet\UDB3\Model\ValueObject\Geography;

use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\IsNotEmpty;
use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\IsString;

class Street
{
    use IsString;
    use IsNotEmpty;

    public function __construct($value)
    {
        $this->guardNotEmpty($value);
        $this->setValue($value);
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Audience;

use CultuurNet\UDB3\Model\ValueObject\Integer\Behaviour\IsInteger;
use CultuurNet\UDB3\Model\ValueObject\Integer\Behaviour\IsNatural;

class Age
{
    use IsInteger;
    use IsNatural;

    public function __construct(int $value)
    {
        $this->guardNatural($value);
        $this->setValue($value);
    }
}

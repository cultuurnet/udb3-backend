<?php

namespace CultuurNet\UDB3\Model\ValueObject\Audience;

use CultuurNet\UDB3\Model\ValueObject\Integer\Behaviour\IsInteger;
use CultuurNet\UDB3\Model\ValueObject\Integer\Behaviour\IsNatural;

class Age
{
    use IsInteger;
    use IsNatural;

    /**
     * @param int $value
     */
    public function __construct($value)
    {
        $this->guardNatural($value);
        $this->setValue($value);
    }
}

<?php

namespace CultuurNet\UDB3\Model\ValueObject\Contact;

use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\IsNotEmpty;
use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\IsString;

class TelephoneNumber
{
    use IsString;
    use IsNotEmpty;

    /**
     * @param string $value
     */
    public function __construct($value)
    {
        $this->guardNotEmpty($value);
        $this->setValue($value);
    }
}

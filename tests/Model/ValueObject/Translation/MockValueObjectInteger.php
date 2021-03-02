<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Translation;

use CultuurNet\UDB3\Model\ValueObject\Integer\Behaviour\IsInteger;

class MockValueObjectInteger
{
    use IsInteger;

    /**
     * @param int $value
     */
    public function __construct($value)
    {
        $this->setValue($value);
    }
}

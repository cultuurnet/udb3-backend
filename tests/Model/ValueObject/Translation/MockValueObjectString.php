<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Translation;

use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\IsString;

class MockValueObjectString
{
    use IsString;

    /**
     * @param string $value
     */
    public function __construct($value)
    {
        $this->setValue($value);
    }
}

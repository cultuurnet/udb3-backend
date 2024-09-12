<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Translation;

use CultuurNet\UDB3\Model\ValueObject\Integer\Behaviour\IsInteger;

class MockValueObjectInteger
{
    use IsInteger;

    public function __construct(int $value)
    {
        $this->setValue($value);
    }
}

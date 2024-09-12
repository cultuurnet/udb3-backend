<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Integer\Behaviour;

class MockNatural
{
    use IsInteger;
    use IsNatural;

    public function __construct(int $value)
    {
        $this->guardNatural($value);
        $this->setValue($value);
    }
}

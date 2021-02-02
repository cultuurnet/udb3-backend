<?php

namespace CultuurNet\UDB3\Model\ValueObject\String\Behaviour;

class MockString
{
    use IsString;

    public function __construct($value)
    {
        $this->setValue($value);
    }
}

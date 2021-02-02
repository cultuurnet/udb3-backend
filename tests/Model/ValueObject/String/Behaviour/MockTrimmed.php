<?php

namespace CultuurNet\UDB3\Model\ValueObject\String\Behaviour;

class MockTrimmed
{
    use IsString;
    use Trims;

    public function __construct($value)
    {
        $value = $this->trim($value);
        $this->setValue($value);
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\String\Behaviour;

class MockNotEmptyString
{
    use IsString;
    use IsNotEmpty;

    public function __construct($value)
    {
        $this->guardString($value);
        $this->guardNotEmpty($value);
        $this->setValue($value);
    }
}

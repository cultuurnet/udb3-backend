<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\String\Behaviour;

class MockString
{
    use IsString;

    public function __construct(string $value)
    {
        $this->setValue($value);
    }
}

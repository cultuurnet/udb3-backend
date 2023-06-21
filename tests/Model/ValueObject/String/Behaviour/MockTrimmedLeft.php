<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\String\Behaviour;

class MockTrimmedLeft
{
    use IsString;
    use Trims;

    public function __construct(string $value)
    {
        $value = $this->trimLeft($value);
        $this->setValue($value);
    }
}

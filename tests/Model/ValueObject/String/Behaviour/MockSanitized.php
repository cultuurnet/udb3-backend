<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\String\Behaviour;

class MockSanitized
{
    use IsString;
    use Sanitizes;

    public function __construct(string $value)
    {
        $value = $this->sanitize($value);
        $this->setValue($value);
    }
}

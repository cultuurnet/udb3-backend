<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\String\Behaviour;

class MockHasMaxLengthString
{
    use HasMaxLength;

    private string $value;

    public function __construct(string $value, int $maxLength)
    {
        $this->guardTooLong($value, $maxLength);
        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}

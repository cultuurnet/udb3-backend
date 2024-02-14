<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\String\Behaviour;

class MockHasMaxLength
{
    use HasMaxLength;

    private bool $success;

    public function __construct(string $value, int $maxLength)
    {
        $this->hasMaxLength($value, $maxLength);

        $this->success = true;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }
}

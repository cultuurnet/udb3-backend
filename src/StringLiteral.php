<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

/**
 * @deprecated Should not be used. Only here to get rid of ValueObjects dependency
 */
class StringLiteral
{
    protected string $value;

    /**
     * @return static
     */
    public static function fromNative(string $value)
    {
        // @phpstan-ignore-next-line
        return new static($value);
    }

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function toNative(): string
    {
        return $this->value;
    }

    public function sameValueAs(StringLiteral $stringLiteral): bool
    {
        return $this->toNative() === $stringLiteral->toNative();
    }

    public function isEmpty(): bool
    {
        return $this->toNative() === '';
    }

    public function __toString(): string
    {
        return $this->toNative();
    }
}

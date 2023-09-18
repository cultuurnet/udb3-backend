<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\ValueObjects;

class Query
{
    private string $value;

    public function __construct(string $value)
    {
        if (empty($value)) {
            throw new \InvalidArgumentException('Query can\'t be empty.');
        }

        $this->value = $value;
    }

    public function toNative(): string
    {
        return $this->value;
    }

    public function isEmpty(): bool
    {
        return empty($this->value);
    }

    public function sameValueAs(Query $other): bool
    {
        return $this->value === $other->value;
    }
}

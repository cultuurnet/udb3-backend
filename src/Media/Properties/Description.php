<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media\Properties;

final class Description
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function toNative(): string
    {
        return $this->value;
    }

    public function sameValueAs(Description $description): bool
    {
        return $this->toNative() === $description->toNative();
    }
}

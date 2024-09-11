<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Curators;

use InvalidArgumentException;

final class PublisherName
{
    private string $name;

    public function __construct(string $name)
    {
        if (!self::isValid($name)) {
            throw new InvalidArgumentException('Invalid publisher: ' . $name);
        }
        $this->name = $name;
    }

    private static function isValid(string $name): bool
    {
        return !empty($name);
    }

    public function toString(): string
    {
        return $this->name;
    }

    public function equals(PublisherName $other): bool
    {
        return mb_strtolower($this->name) === mb_strtolower($other->name);
    }
}

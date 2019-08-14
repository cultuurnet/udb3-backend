<?php

namespace CultuurNet\UDB3\Curators;

use InvalidArgumentException;

class Publisher
{
    private const BRUZZ = 'bruzz';

    private static $knownPublishers = [
        self::BRUZZ,
    ];

    /**
     * @var string
     */
    private $name;

    private function __construct(string $name)
    {
        if (!self::isValid($name)) {
            throw new InvalidArgumentException('Unknown publisher: ' . $name);
        }
        $this->name = $name;
    }

    public static function bruzz(): self
    {
        return new self(self::BRUZZ);
    }

    public static function fromName(string $name): self
    {
        return new self($name);
    }

    private static function isValid(string $name): bool
    {
        return in_array($name, self::$knownPublishers);
    }
}

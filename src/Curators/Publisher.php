<?php

namespace CultuurNet\UDB3\Curators;

use InvalidArgumentException;

class Publisher
{
    /**
     * @var string
     */
    private $name;

    private function __construct(string $name)
    {
        if (!self::isValid($name)) {
            throw new InvalidArgumentException('Invalid publisher: ' . $name);
        }
        $this->name = $name;
    }

    public static function fromName(string $name): self
    {
        return new self($name);
    }

    private static function isValid(string $name): bool
    {
        return !empty($name);
    }

    public function getName(): string
    {
        return $this->name;
    }
}

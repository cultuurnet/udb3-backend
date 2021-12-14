<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\String;

use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\IsString;
use InvalidArgumentException;

abstract class Enum
{
    use IsString;

    final public function __construct(string $value)
    {
        $this->guardString($value);
        $this->guardAllowedValue($value);
        $this->setValue($value);
    }

    /**
     * @throws InvalidArgumentException
     */
    private function guardAllowedValue(string $value): void
    {
        $allowed = static::getAllowedValues();
        if (!in_array($value, $allowed)) {
            throw new InvalidArgumentException(
                "Encountered unknown value '{$value}'. Allowed values: " . implode(', ', $allowed)
            );
        }
    }

    /**
     * @return string[]
     */
    public static function getAllowedValues(): array
    {
        return [];
    }

    public static function __callStatic(string $name, array $arguments): Enum
    {
        return new static($name);
    }
}

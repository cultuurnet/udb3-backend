<?php

namespace CultuurNet\UDB3\Model\ValueObject\String;

use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\IsString;

abstract class Enum
{
    use IsString;

    /**
     * @param string $value
     */
    final public function __construct($value)
    {
        $this->guardString($value);
        $this->guardAllowedValue($value);
        $this->setValue($value);
    }

    /**
     * @param string $value
     * @throws \InvalidArgumentException
     */
    private function guardAllowedValue($value)
    {
        $allowed = static::getAllowedValues();
        if (!in_array($value, $allowed)) {
            throw new \InvalidArgumentException(
                "Encountered unknown value '{$value}'. Allowed values: " . implode(', ', $allowed)
            );
        }
    }

    /**
     * @return string[]
     */
    public static function getAllowedValues()
    {
        return [];
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return static
     */
    public static function __callStatic($name, $arguments)
    {
        return new static($name);
    }
}

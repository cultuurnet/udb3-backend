<?php

namespace CultuurNet\UDB3\Geocoding\Coordinate;

abstract class Coordinate
{
    /**
     * @var double
     */
    private $value;

    /**
     * @param double $value
     */
    public function __construct($value)
    {
        if (!is_double($value)) {
            throw new \InvalidArgumentException('Coordinate value should be of type double.');
        }

        $this->value = $value;
    }

    /**
     * @return double
     */
    public function toDouble()
    {
        return $this->value;
    }

    /**
     * @param Coordinate $coordinate
     * @return bool
     */
    public function sameAs(Coordinate $coordinate)
    {
        return $this->value === $coordinate->value;
    }
}

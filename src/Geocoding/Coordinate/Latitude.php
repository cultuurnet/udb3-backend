<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Geocoding\Coordinate;

class Latitude extends Coordinate
{
    public function __construct($value)
    {
        parent::__construct($value);

        if ($value < -90 || $value > 90) {
            throw new \InvalidArgumentException('Latitude should be between -90 and 90.');
        }
    }
}

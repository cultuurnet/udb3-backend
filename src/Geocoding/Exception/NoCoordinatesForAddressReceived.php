<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Geocoding\Exception;

use RuntimeException;

class NoCoordinatesForAddressReceived extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Coordinates from address are empty');
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Geocoding;

use CultuurNet\UDB3\Geocoding\Coordinate\Coordinates;

interface GeocodingService
{
    /**
     * Gets the coordinates of the given address.
     * Returns null when no coordinates are found for the given address.
     * This can happen in case of a wrong/unknown address.
     */
    public function getCoordinates(string $address): ?Coordinates;
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Geocoding;

use CultuurNet\UDB3\Geocoding\Coordinate\Coordinates;

interface GeocodingServiceInterface
{
    /**
     * Gets the coordinates of the given address.
     * Returns null when no coordinates are found for the given address.
     * This can happen in case of a wrong/unknown address.
     *
     * @param string $address
     * @return Coordinates|null
     */
    public function getCoordinates($address);
}

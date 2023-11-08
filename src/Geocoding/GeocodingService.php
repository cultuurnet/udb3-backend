<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Geocoding;

use Geocoder\Location;

interface GeocodingService
{
    /**
     * Gets the details of the given address.
     * Returns null when no coordinates are found for the given address.
     * This can happen in case of a wrong/unknown address.
     */
    public function fetchAddress(string $address): ?Location;
}

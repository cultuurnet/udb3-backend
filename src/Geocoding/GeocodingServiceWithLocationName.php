<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Geocoding;

/**
 * Like the DefaultGeocodingService, but we are also sending the location name
 */
class GeocodingServiceWithLocationName extends AbstractGeocodingService
{
    public function searchTerm(string $address, string $locationName): string
    {
        return trim($locationName . ' ' . $address);
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Geocoding;

/**
 * Like the DefaultGeocodingService, but we are also sending the location name
 */
class EnrichedGeocodingService extends AbstractGeocodingService
{
    protected function getKey(string $address, string $locationName): string
    {
        return trim($address . ' ' . $locationName);
    }
}

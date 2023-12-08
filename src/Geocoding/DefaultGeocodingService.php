<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Geocoding;

class DefaultGeocodingService extends AbstractGeocodingService
{
    protected function getKey(string $address, string $locationName): string
    {
        return $address;
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Geocoding\CacheEncoder;

use Geocoder\Location;

class CoordinateEncoder implements CacheEncoder
{
    public function encode(Location $location): array
    {
        if ($location->getCoordinates() === null) {
            return [];
        }

        return [
            'lat' => $location->getCoordinates()->getLatitude(),
            'long' => $location->getCoordinates()->getLongitude(),
        ];
    }

    public function getKey(string $address): string
    {
        return $address;
    }
}

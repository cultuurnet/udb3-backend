<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Geocoding\CacheEncoder;

use Geocoder\Location;

class RichEncoder implements CacheEncoder
{
    private const POSTFIX = '_enriched';

    public function encode(Location $location): array
    {
        if ($location->getCoordinates() === null) {
            return [];
        }

        return [
            'lat' => $location->getCoordinates()->getLatitude(),
            'long' => $location->getCoordinates()->getLongitude(),
            'streetNumber' => $location->getStreetNumber(),
            'streetName' => $location->getStreetName(),
            'locality' => $location->getLocality(),
            'postalCode' => $location->getPostalCode(),
            'country' => $location->getCountry()->getName(),
            'country_code' => $location->getCountry()->getCode(),

        ];
    }

    public function getKey(string $address): string
    {
        return $address . self::POSTFIX;
    }
}

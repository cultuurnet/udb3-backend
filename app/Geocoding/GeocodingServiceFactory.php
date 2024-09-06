<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Geocoding;

use Geocoder\Provider\GoogleMaps\GoogleMaps;
use Geocoder\StatefulGeocoder;
use Http\Adapter\Guzzle7\Client;
use Psr\Log\LoggerInterface;

class GeocodingServiceFactory
{
    public function createService(LoggerInterface $logger, string $googleMapApiKey): GeocodingService
    {
        return new GeocodingServiceWithLocationName(
            new StatefulGeocoder(
                new GoogleMaps(
                    new Client(),
                    null,
                    $googleMapApiKey
                )
            ),
            $logger
        );
    }

    public function getCacheName(): string
    {
        return 'geocoords_with_location_name';
    }
}

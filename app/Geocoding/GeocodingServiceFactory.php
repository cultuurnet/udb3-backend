<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Geocoding;

use Geocoder\Provider\GoogleMaps\GoogleMaps;
use Geocoder\StatefulGeocoder;
use Http\Adapter\Guzzle7\Client;
use Psr\Log\LoggerInterface;

class GeocodingServiceFactory
{
    private bool $addLocationNameToCoordinatesLookup;

    public function __construct(bool $addLocationNameToCoordinatesLookup)
    {
        $this->addLocationNameToCoordinatesLookup = $addLocationNameToCoordinatesLookup;
    }

    public function createService(LoggerInterface $logger, string $googleMapApiKey): GeocodingService
    {
        if ($this->addLocationNameToCoordinatesLookup) {
            return $this->createEnrichedService($logger, $googleMapApiKey);
        }

        return $this->createBasicService($logger, $googleMapApiKey);
    }

    public function getCacheName(): string
    {
        return ($this->addLocationNameToCoordinatesLookup) ? 'geocoords_with_location_name' : 'geocoords';
    }

    private function createEnrichedService(LoggerInterface $logger, string $googleMapApiKey): GeocodingService
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

    private function createBasicService(LoggerInterface $logger, string $googleMapApiKey): GeocodingService
    {
        return new DefaultGeocodingService(
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
}

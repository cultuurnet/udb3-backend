<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Geocoding;

use CultuurNet\UDB3\Geocoding\Coordinate\Coordinates;
use Exception;
use Geocoder\Geocoder;
use Geocoder\Location;
use Psr\Log\LoggerInterface;

class DefaultGeocodingService implements GeocodingService
{
    private Geocoder $geocoder;

    private LoggerInterface $logger;

    public function __construct(
        Geocoder $geocoder,
        LoggerInterface $logger
    ) {
        $this->geocoder = $geocoder;
        $this->logger = $logger;
    }

    public function fetchAddress(string $address): ?Location
    {
        try {
            return $this->geocoder->geocode($address)->first();
        }
        catch(Exception $e) {
            $this->logger->warning(
                'No results for address: "' . $address . '". Exception message: ' . $e->getMessage()
            );
        }

        return null;
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Geocoding;

use CultuurNet\UDB3\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Geocoding\Exception\NoCoordinatesForAddressReceived;
use CultuurNet\UDB3\Geocoding\Exception\NoGoogleAddressReceived;
use Psr\Log\LoggerInterface;

class GeocodingService implements HasCoordinates
{
    private GeocodingCacheFacade $geocoder;

    private LoggerInterface $logger;

    public function __construct(
        GeocodingCacheFacade $geocoder,
        LoggerInterface $logger
    ) {
        $this->geocoder = $geocoder;
        $this->logger = $logger;
    }

    public function getCoordinates(string $address, ?string $locationName = null): ?Coordinates
    {
        try {
            return $this->geocoder->fetchCoordinates($address, $locationName);
        } catch (NoGoogleAddressReceived|NoCoordinatesForAddressReceived $e) {
            $this->logger->warning(
                'No results for address: "' . $address . '". Exception message: ' . $e->getMessage()
            );
        }

        return null;
    }
}

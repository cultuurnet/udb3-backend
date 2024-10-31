<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Geocoding;

use CultuurNet\UDB3\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Geocoding\Coordinate\Latitude;
use CultuurNet\UDB3\Geocoding\Coordinate\Longitude;
use Geocoder\Exception\CollectionIsEmpty;
use Geocoder\Model\Coordinates as GeocoderCoordinates;
use Geocoder\Exception\Exception;
use Geocoder\Geocoder;
use Psr\Log\LoggerInterface;

abstract class AbstractGeocodingService implements GeocodingService
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

    // We do not use $locationName on purpose - see GeocodingServiceWithLocationName
    public function getCoordinates(string $address, string $locationName=''): ?Coordinates
    {
        try {
            $addresses = $this->geocoder->geocode($this->searchTerm($address, $locationName));
            /** @var GeocoderCoordinates|null $coordinates */
            $coordinates = $addresses->first()->getCoordinates();

            if ($coordinates === null) {
                throw new CollectionIsEmpty('Coordinates from address are empty');
            }

            return new Coordinates(
                new Latitude($coordinates->getLatitude()),
                new Longitude($coordinates->getLongitude())
            );
        } catch (Exception|CollectionIsEmpty $exception) {
            $this->logger->warning(
                'No results for address: "' . $address . '". Exception message: ' . $exception->getMessage()
            );
            return null;
        }
    }
}

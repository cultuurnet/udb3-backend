<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Geocoding;

use CultuurNet\UDB3\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Geocoding\Coordinate\Latitude;
use CultuurNet\UDB3\Geocoding\Coordinate\Longitude;
use Geocoder\Exception\CollectionIsEmpty;
use Geocoder\Exception\Exception;
use Geocoder\Geocoder;
use Psr\Log\LoggerInterface;

class DefaultGeocodingService implements GeocodingServiceInterface
{
    /**
     * @var Geocoder
     */
    private $geocoder;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Geocoder $geocoder,
        LoggerInterface $logger
    ) {
        $this->geocoder = $geocoder;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function getCoordinates($address)
    {
        try {
            $addresses = $this->geocoder->geocode($address);
            $coordinates = $addresses->first()->getCoordinates();

            if ($coordinates === null) {
                throw new CollectionIsEmpty('Coordinates from address are empty');
            }

            return new Coordinates(
                new Latitude($coordinates->getLatitude()),
                new Longitude($coordinates->getLongitude())
            );
        } catch (Exception|CollectionIsEmpty $exception) {
            $this->logger->error(
                'No results for address: "' . $address . '". Exception message: ' . $exception->getMessage()
            );
            return null;
        }
    }
}

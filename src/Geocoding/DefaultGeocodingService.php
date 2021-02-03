<?php

namespace CultuurNet\UDB3\Geocoding;

use CultuurNet\UDB3\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Geocoding\Coordinate\Latitude;
use CultuurNet\UDB3\Geocoding\Coordinate\Longitude;
use Geocoder\Exception\NoResultException;
use Geocoder\GeocoderInterface;
use Psr\Log\LoggerInterface;

class DefaultGeocodingService implements GeocodingServiceInterface
{
    /**
     * @var GeocoderInterface
     */
    private $geocoder;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param GeocoderInterface $geocoder
     * @param LoggerInterface $logger
     */
    public function __construct(
        GeocoderInterface $geocoder,
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
            $result = $this->geocoder->geocode($address);
            $coordinates = $result->getCoordinates();

            return new Coordinates(
                new Latitude((double)$coordinates[0]),
                new Longitude((double)$coordinates[1])
            );
        } catch (NoResultException $exception) {
            $this->logger->error(
                'No results for address: "' . $address . '". Exception message: ' . $exception->getMessage()
            );
            return null;
        }
    }
}

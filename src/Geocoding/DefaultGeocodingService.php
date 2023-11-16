<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Geocoding;

use CultuurNet\UDB3\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Geocoding\Dto\EnrichedAddress;
use Geocoder\Exception\Exception;
use Geocoder\Geocoder;
use Psr\Log\LoggerInterface;

class DefaultGeocodingService implements GeocodingService
{
    private Geocoder $geocoder;

    private LoggerInterface $logger;
    private array $cache = [];

    public function __construct(
        Geocoder $geocoder,
        LoggerInterface $logger
    ) {
        $this->geocoder = $geocoder;
        $this->logger = $logger;
    }

    public function getCoordinates(string $address, ?string $locationName=null): ?Coordinates
    {
        try {
            $key = $address . $locationName;
            if (!isset($this->cache[$key])) {
                $this->cache[$key] = new GeocodingResponse($this->geocoder, $address, $locationName);
            }

            return $this->cache[$key]->getCoordinates();
        } catch (Exception $e) {
            $this->logger->warning(
                'No results for address: "' . $address . '". Exception message: ' . $e->getMessage()
            );
        }

        return null;
    }

    public function getEnrichedAddress(string $address, ?string $locationName): ?EnrichedAddress
    {
        try {
            $key = $address . $locationName;
            if (!isset($this->cache[$key])) {
                $this->cache[$key] = new GeocodingResponse($this->geocoder, $address, $locationName);
            }

            return $this->cache[$key]->getEnrichedAddress();
        } catch (Exception $e) {
            $this->logger->warning(
                'No results for address: "' . $address . '". Exception message: ' . $e->getMessage()
            );
        }

        return $this->cache[$key]->getEnrichedAddress();
    }
}

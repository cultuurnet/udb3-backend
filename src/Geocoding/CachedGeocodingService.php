<?php

namespace CultuurNet\UDB3\Geocoding;

use CultuurNet\UDB3\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Geocoding\Coordinate\Latitude;
use CultuurNet\UDB3\Geocoding\Coordinate\Longitude;
use Doctrine\Common\Cache\Cache;

class CachedGeocodingService implements GeocodingServiceInterface
{
    const NO_COORDINATES_FOUND = 'NO_COORDINATES_FOUND';

    /**
     * @var GeocodingServiceInterface
     */
    private $geocodingService;

    /**
     * @var Cache
     */
    private $cache;

    /**
     * @param GeocodingServiceInterface $geocodingService
     * @param Cache $cache
     */
    public function __construct(GeocodingServiceInterface $geocodingService, Cache $cache)
    {
        $this->geocodingService = $geocodingService;
        $this->cache = $cache;
    }

    /**
     * @inheritdoc
     */
    public function getCoordinates($address)
    {
        $encodedCacheData = $this->cache->fetch($address);

        if ($encodedCacheData) {
            $cacheData = json_decode($encodedCacheData, true);

            // Some addresses have no coordinates, to cache these addresses 'NO_COORDINATES_FOUND' is used as value.
            // When the 'NO_COORDINATES_FOUND' cached value is found null is returned as coordinate.
            if (self::NO_COORDINATES_FOUND === $cacheData) {
                return null;
            }

            if (isset($cacheData['lat']) && isset($cacheData['long'])) {
                return new Coordinates(
                    new Latitude((double) $cacheData['lat']),
                    new Longitude((double) $cacheData['long'])
                );
            }
        }

        $coordinates = $this->geocodingService->getCoordinates($address);

        // Some addresses have no coordinates, to cache these addresses 'NO_COORDINATES_FOUND' is used as value.
        // When null is passed in as the coordinates, then 'NO_COORDINATES_FOUND' is stored as cache value.
        $cacheData = self::NO_COORDINATES_FOUND;
        if ($coordinates) {
            $cacheData = [
                'lat' => $coordinates->getLatitude()->toDouble(),
                'long' => $coordinates->getLongitude()->toDouble(),
            ];
        }

        $this->cache->save($address, json_encode($cacheData));

        return $coordinates;
    }
}

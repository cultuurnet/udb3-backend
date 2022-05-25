<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Geocoding;

use CultuurNet\UDB3\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Geocoding\Coordinate\Latitude;
use CultuurNet\UDB3\Geocoding\Coordinate\Longitude;
use CultuurNet\UDB3\Json;
use Doctrine\Common\Cache\Cache;

class CachedGeocodingService implements GeocodingService
{
    public const NO_COORDINATES_FOUND = 'NO_COORDINATES_FOUND';

    private GeocodingService $geocodingService;

    private Cache $cache;

    public function __construct(GeocodingService $geocodingService, Cache $cache)
    {
        $this->geocodingService = $geocodingService;
        $this->cache = $cache;
    }

    public function getCoordinates($address): ?Coordinates
    {
        $encodedCacheData = $this->cache->fetch($address);

        if ($encodedCacheData) {
            $cacheData = Json::decodeAssociatively($encodedCacheData);

            // Some addresses have no coordinates, to cache these addresses 'NO_COORDINATES_FOUND' is used as value.
            // When the 'NO_COORDINATES_FOUND' cached value is found null is returned as coordinate.
            if (self::NO_COORDINATES_FOUND === $cacheData) {
                return null;
            }

            if (isset($cacheData['lat'], $cacheData['long'])) {
                return new Coordinates(
                    new Latitude((float) $cacheData['lat']),
                    new Longitude((float) $cacheData['long'])
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

        $this->cache->save($address, Json::encode($cacheData));

        return $coordinates;
    }
}

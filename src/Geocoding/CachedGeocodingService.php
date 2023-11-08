<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Geocoding;

use CultuurNet\UDB3\Geocoding\CacheEncoder\CacheEncoder;
use CultuurNet\UDB3\Json;
use Doctrine\Common\Cache\Cache;
use Geocoder\Location;
use Geocoder\Model\Address;
use Geocoder\Model\AdminLevelCollection;
use Geocoder\Model\Coordinates;

class CachedGeocodingService implements GeocodingService
{
    public const NO_COORDINATES_FOUND = 'NO_COORDINATES_FOUND';

    private GeocodingService $geocodingService;

    private Cache $cache;

    private CacheEncoder $cacheEncoder;

    public function __construct(GeocodingService $geocodingService, Cache $cache, CacheEncoder $cacheEncoder)
    {
        $this->geocodingService = $geocodingService;
        $this->cache = $cache;
        $this->cacheEncoder = $cacheEncoder;
    }

    public function fetchAddress(string $address): ?Location
    {
        $encodedCacheData = $this->cache->fetch($this->cacheEncoder->getKey($address));

        if ($encodedCacheData) {
            $cacheData = Json::decodeAssociatively($encodedCacheData);

            // Some addresses have no coordinates, to cache these addresses 'NO_COORDINATES_FOUND' is used as value.
            // When the 'NO_COORDINATES_FOUND' cached value is found null is returned as coordinate.
            if (self::NO_COORDINATES_FOUND === $cacheData) {
                return null;
            }

            if (isset($cacheData['lat'], $cacheData['long'])) {
                return $this->constructAddressFromCache($cacheData);
            }
        }

        $enrichedAddress = $this->geocodingService->fetchAddress($address);

        // Some addresses have no coordinates, to cache these addresses 'NO_COORDINATES_FOUND' is used as value.
        // When null is passed in as the coordinates, then 'NO_COORDINATES_FOUND' is stored as cache value.
        $cacheData = self::NO_COORDINATES_FOUND;
        if ($enrichedAddress) {
            $cacheData = $this->cacheEncoder->encode($enrichedAddress);
        }

        $this->cache->save($this->cacheEncoder->getKey($address), Json::encode($cacheData));

        if ($cacheData === self::NO_COORDINATES_FOUND) {
            return null;
        }

        return $this->constructAddressFromCache($cacheData);
    }

    private function constructAddressFromCache(array $cacheData): Address
    {
        return new Address(
            'cache',
            new AdminLevelCollection(),
            new Coordinates(
                $cacheData['lat'],
                $cacheData['long']
            )
        );
    }
}

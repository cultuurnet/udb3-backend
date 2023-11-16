<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Geocoding;

use CultuurNet\UDB3\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Geocoding\Dto\EnrichedAddress;
use Doctrine\Common\Cache\Cache;

class EnrichedCachedGeocodingService implements GeocodingService
{
    private GeocodingService $geocodingService;

    private Cache $cache;

    public function __construct(GeocodingService $geocodingService, Cache $cache)
    {
        $this->geocodingService = $geocodingService;
        $this->cache = $cache;
    }

    public function getCoordinates(string $address, ?string $locationName=null): ?Coordinates
    {
        $key = $address . $locationName;

        if (!$this->cache->contains($key)) {
            try {
                $this->cache->save($key, $this->geocodingService->getEnrichedAddress($address, $locationName));
            } catch (NoGoogleAddressToEnrichReceived $e) {
                // No google address to save
            }
        }

        return $this->geocodingService->getCoordinates($address, $locationName);
    }

    public function getEnrichedAddress(string $address, ?string $locationName): EnrichedAddress
    {
        return $this->geocodingService->getEnrichedAddress($address, $locationName);
    }
}

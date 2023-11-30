<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Geocoding;

use CultuurNet\UDB3\Geocoding\Dto\EnrichedAddress;
use Doctrine\Common\Cache\Cache;

class EnrichedCachedGeocodingService
{
    private GeocodingCacheFacade $geocoder;
    private Cache $cache;
    private bool $addressEnrichment;

    public function __construct(GeocodingCacheFacade $geocoder, Cache $cache, bool $addressEnrichment)
    {
        $this->geocoder = $geocoder;
        $this->cache = $cache;
        $this->addressEnrichment = $addressEnrichment;
    }

    public function saveEnrichedAddress(string $address, ?string $locationName): ?EnrichedAddress
    {
        if (! $this->addressEnrichment) {
            return null;
        }

        $key = $address . $locationName;

        if ($this->cache->contains($key)) {
            return $this->cache->fetch($key);
        }

        return $this->geocoder->fetchEnrichedAddress($address, $locationName);
    }
}

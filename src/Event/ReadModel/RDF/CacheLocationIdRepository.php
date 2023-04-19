<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\RDF;

use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use Doctrine\Common\Cache\Cache;

final class CacheLocationIdRepository implements LocationIdRepository
{
    private Cache $cache;

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    public function save(string $resourceId, LocationId $locationId): void
    {
        $this->cache->save($resourceId, $locationId->toString());
    }

    public function get(string $resourceId): ?LocationId
    {
        $value = $this->cache->fetch($resourceId);
        if ($value === false) {
            return null;
        }
        return new LocationId($value);
    }
}

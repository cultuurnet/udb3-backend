<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Address\StreetSuggester;

use Symfony\Contracts\Cache\CacheInterface;

final class CachedBPostStreetSuggester implements StreetSuggester
{
    private StreetSuggester $baseStreetSuggester;

    private CacheInterface $cache;

    public function __construct(StreetSuggester $streetSuggester, CacheInterface $cache)
    {
        $this->baseStreetSuggester = $streetSuggester;
        $this->cache = $cache;
    }

    public function suggest(
        string $postalCode,
        string $locality,
        string $streetQuery,
        int $limit = 5
    ): array {
        return $this->cache->get(
            $this->createCacheKey($postalCode, $locality, $streetQuery, $limit),
            fn () => $this->baseStreetSuggester->suggest($postalCode, $locality, $streetQuery, $limit)
        );
    }

    private function createCacheKey(string $postalCode, string $locality, string $streetQuery, int $limit): string
    {
        return preg_replace('/[{}()\/\\\\@:]/', '_', $postalCode . '_' . $locality . '_' . $streetQuery . '_' . (string) $limit);
    }
}

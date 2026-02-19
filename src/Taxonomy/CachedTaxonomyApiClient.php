<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Taxonomy;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use Symfony\Contracts\Cache\CacheInterface;

final class CachedTaxonomyApiClient implements TaxonomyApiClient
{
    private TaxonomyApiClient $baseTaxonomyApiClient;

    private CacheInterface $cache;

    public function __construct(TaxonomyApiClient $taxonomyApiClient, CacheInterface $cache)
    {
        $this->baseTaxonomyApiClient = $taxonomyApiClient;
        $this->cache = $cache;
    }

    public function getMapping(): array
    {
        return $this->cache->get(
            'mapping',
            fn () => $this->baseTaxonomyApiClient->getMapping()
        );
    }

    /**
     * @return  Category[]
     */
    public function getEventTypes(): array
    {
        return $this->cache->get(
            'event_types',
            fn () => $this->baseTaxonomyApiClient->getEventTypes()
        );
    }

    /**
     * @return  Category[]
     */
    public function getEventThemes(): array
    {
        return $this->cache->get(
            'event_themes',
            fn () => $this->baseTaxonomyApiClient->getEventThemes()
        );
    }

    /**
     * @return  Category[]
     */
    public function getEventFacilities(): array
    {
        return $this->cache->get(
            'event_facilities',
            fn () => $this->baseTaxonomyApiClient->getEventFacilities()
        );
    }

    /**
     * @return  Category[]
     */
    public function getPlaceTypes(): array
    {
        return $this->cache->get(
            'place_types',
            fn () => $this->baseTaxonomyApiClient->getPlaceTypes()
        );
    }

    /**
     * @return  Category[]
     */
    public function getPlaceFacilities(): array
    {
        return $this->cache->get(
            'place_facilities',
            fn () => $this->baseTaxonomyApiClient->getPlaceFacilities()
        );
    }
}

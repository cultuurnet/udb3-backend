<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Taxonomy;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Categories;
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

    public function getNativeTerms(): array
    {
        return $this->cache->get(
            'mapping',
            fn () => $this->baseTaxonomyApiClient->getNativeTerms()
        );
    }

    public function getEventTypes(): Categories
    {
        return $this->cache->get(
            'event_types',
            fn () => $this->baseTaxonomyApiClient->getEventTypes()
        );
    }

    public function getEventThemes(): Categories
    {
        return $this->cache->get(
            'event_themes',
            fn () => $this->baseTaxonomyApiClient->getEventThemes()
        );
    }

    public function getEventFacilities(): Categories
    {
        return $this->cache->get(
            'event_facilities',
            fn () => $this->baseTaxonomyApiClient->getEventFacilities()
        );
    }

    public function getPlaceTypes(): Categories
    {
        return $this->cache->get(
            'place_types',
            fn () => $this->baseTaxonomyApiClient->getPlaceTypes()
        );
    }

    public function getPlaceFacilities(): Categories
    {
        return $this->cache->get(
            'place_facilities',
            fn () => $this->baseTaxonomyApiClient->getPlaceFacilities()
        );
    }
}

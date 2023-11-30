<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Geocoding;

use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Error\LoggerFactory;
use CultuurNet\UDB3\Error\LoggerName;
use Geocoder\Provider\GoogleMaps\GoogleMaps;
use Geocoder\StatefulGeocoder;
use Http\Adapter\Guzzle7\Client;

final class GeocodingServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            GeocodingService::class,
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            GeocodingCacheFacade::class,
            function () use ($container) {
                return new StatefulGeocoder(
                    new GoogleMaps(
                        new Client(),
                        null,
                        $container->get('config')['google_maps_api_key'] ?? null
                    )
                );
            }
        );

        $container->addShared(
            EnrichedCachedGeocodingService::class,
            function () use ($container) {
                return new EnrichedCachedGeocodingService(
                    $container->get(GeocodingCacheFacade::class),
                    $container->get('cache')('geocoords_enriched'),
                    $container->get('config')['address_enrichment']
                );
            }
        );

        $container->addShared(
            GeocodingService::class,
            function () use ($container) {
                return new CachedGeocodingService(
                    new GeocodingService(
                        $container->get(GeocodingCacheFacade::class),
                        LoggerFactory::create($container, LoggerName::forService('geo-coordinates', 'google'))
                    ),
                    $container->get('cache')('geocoords')
                );
            }
        );
    }
}

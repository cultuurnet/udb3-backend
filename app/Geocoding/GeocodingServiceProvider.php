<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Geocoding;

use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Error\LoggerFactory;
use CultuurNet\UDB3\Error\LoggerName;
use CultuurNet\UDB3\Geocoding\CacheEncoder\CoordinateEncoder;
use CultuurNet\UDB3\Geocoding\CacheEncoder\RichEncoder;
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
            GeocodingService::class,
            function () use ($container) {
                $googleMapsApiKey = $container->get('config')['google_maps_api_key'] ?? null;

                $geocodingService = new DefaultGeocodingService(
                    new StatefulGeocoder(
                        new GoogleMaps(
                            new Client(),
                            null,
                            $googleMapsApiKey
                        )
                    ),
                    LoggerFactory::create($container, LoggerName::forService('geo-coordinates', 'google'))
                );

                return new CachedGeocodingService(
                    $geocodingService,
                    $container->get('cache')('geocoords'),
                    empty($container->get('config')['address_enrichment']) ? new CoordinateEncoder() : new RichEncoder()
                );
            }
        );
    }
}

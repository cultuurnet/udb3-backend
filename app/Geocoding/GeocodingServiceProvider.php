<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Geocoding;

use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Error\LoggerFactory;
use CultuurNet\UDB3\Error\LoggerName;

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

        $geocodingService = new GeocodingServiceFactory(
            $container->get('config')['add_location_name_to_coordinates_lookup'] ?? false
        );

        $container->addShared(
            GeocodingService::class,
            function () use ($container, $geocodingService) {
                $googleMapsApiKey = $container->get('config')['google_maps_api_key'] ?? null;

                return new CachedGeocodingService(
                    $geocodingService->createService(
                        LoggerFactory::create(
                            $container,
                            LoggerName::forService('geo-coordinates', 'google')
                        ),
                        $googleMapsApiKey,
                    ),
                    $container->get('cache')($geocodingService->getCacheName())
                );
            }
        );
    }
}

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

        $container->addShared(
            GeocodingService::class,
            function () use ($container) {
                $geocodingServiceFactory = new GeocodingServiceFactory();

                return new CachedGeocodingService(
                    $geocodingServiceFactory->createService(
                        LoggerFactory::create(
                            $container,
                            LoggerName::forService('geo-coordinates', 'google')
                        ),
                        $container->get('config')['google_maps_api_key'],
                    ),
                    $container->get('cache')($geocodingServiceFactory->getCacheName())
                );
            }
        );
    }
}

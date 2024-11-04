<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

use CultuurNet\UDB3\Address\CultureFeed\CultureFeedAddressFactory;
use CultuurNet\UDB3\Address\Formatter\FullAddressFormatter;
use CultuurNet\UDB3\Address\Formatter\LocalityAddressFormatter;
use CultuurNet\UDB3\Broadway\EventHandling\ReplayFilteringEventListener;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Error\LoggerFactory;
use CultuurNet\UDB3\Error\LoggerName;
use CultuurNet\UDB3\Geocoding\GeocodingService;

final class PlaceGeoCoordinatesServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            'place_geocoordinates_command_handler',
            'place_geocoordinates_process_manager',
            ExtendedGeoCoordinatesCommandHandler::class,
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            'place_geocoordinates_command_handler',
            function () use ($container) {
                $handler = new GeoCoordinatesCommandHandler(
                    $container->get('place_repository'),
                    new FullAddressFormatter(),
                    new LocalityAddressFormatter(),
                    $container->get(GeocodingService::class),
                    $container->get('place_jsonld_repository')
                );

                $handler->setLogger(LoggerFactory::create($container, LoggerName::forService('geo-coordinates', 'place')));

                return $handler;
            }
        );

        $container->addShared(
            ExtendedGeoCoordinatesCommandHandler::class,
            function () use ($container) {
                return new ExtendedGeoCoordinatesCommandHandler(
                    LoggerFactory::create($container, LoggerName::forService('extended-geo-coordinates', 'place'))
                );
            }
        );

        $container->addShared(
            'place_geocoordinates_process_manager',
            function () use ($container) {
                return new ReplayFilteringEventListener(
                    new GeoCoordinatesProcessManager(
                        $container->get('event_command_bus'),
                        new CultureFeedAddressFactory(),
                        LoggerFactory::create($container, LoggerName::forService('geo-coordinates', 'place')),
                        $container->get('place_jsonld_repository'),
                    )
                );
            }
        );
    }
}

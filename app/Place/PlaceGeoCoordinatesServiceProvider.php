<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

use CultuurNet\UDB3\Broadway\EventHandling\ReplayFilteringEventListener;
use CultuurNet\UDB3\Address\CultureFeedAddressFactory;
use CultuurNet\UDB3\Address\FullAddressFormatter;
use CultuurNet\UDB3\Address\LocalityAddressFormatter;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Geocoding\GeocodingService;
use CultuurNet\UDB3\Error\LoggerFactory;
use CultuurNet\UDB3\Error\LoggerName;

final class PlaceGeoCoordinatesServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            'place_geocoordinates_command_handler',
            'place_geocoordinates_process_manager',
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
                    $container->get('event_jsonld_repository'),
                    $container->get('config')['add_location_name_to_coordinates_lookup'] ?? false
                );

                $handler->setLogger(LoggerFactory::create($container, LoggerName::forService('geo-coordinates', 'place')));

                return $handler;
            }
        );

        $container->addShared(
            'place_geocoordinates_process_manager',
            function () use ($container) {
                return new ReplayFilteringEventListener(
                    new GeoCoordinatesProcessManager(
                        $container->get('event_command_bus'),
                        new CultureFeedAddressFactory(),
                        LoggerFactory::create($container, LoggerName::forService('geo-coordinates', 'place'))
                    )
                );
            }
        );
    }
}

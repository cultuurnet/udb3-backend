<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Place;

use CultuurNet\UDB3\Broadway\EventHandling\ReplayFilteringEventListener;
use CultuurNet\UDB3\Address\CultureFeedAddressFactory;
use CultuurNet\UDB3\Address\DefaultAddressFormatter;
use CultuurNet\UDB3\Address\LocalityAddressFormatter;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Geocoding\GeocodingService;
use CultuurNet\UDB3\Place\GeoCoordinatesCommandHandler;
use CultuurNet\UDB3\Place\GeoCoordinatesProcessManager;
use CultuurNet\UDB3\Error\LoggerFactory;
use CultuurNet\UDB3\Error\LoggerName;
use Silex\Application;

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
                    new DefaultAddressFormatter(),
                    new LocalityAddressFormatter(),
                    $container->get(GeocodingService::class)
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

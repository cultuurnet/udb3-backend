<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use CultuurNet\UDB3\Address\CultureFeed\CultureFeedAddressFactory;
use CultuurNet\UDB3\Address\Formatter\FullAddressFormatter;
use CultuurNet\UDB3\Address\Formatter\LocalityAddressFormatter;
use CultuurNet\UDB3\Broadway\EventHandling\ReplayFilteringEventListener;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Error\LoggerFactory;
use CultuurNet\UDB3\Error\LoggerName;
use CultuurNet\UDB3\Geocoding\GeocodingService;
use Psr\Log\NullLogger;

final class EventGeoCoordinatesServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            'event_geocoordinates_command_handler',
            'event_geocoordinates_process_manager',
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            'event_geocoordinates_command_handler',
            function () use ($container): GeoCoordinatesCommandHandler {
                $handler = new GeoCoordinatesCommandHandler(
                    $container->get('event_repository'),
                    new FullAddressFormatter(),
                    new LocalityAddressFormatter(),
                    $container->get(GeocodingService::class),
                    $container->get('event_jsonld_repository')
                );
                $handler->setLogger(
                    LoggerFactory::create(
                        $container,
                        LoggerName::forService('geo-coordinates', 'event')
                    )
                );

                return $handler;
            }
        );

        $container->addShared(
            'event_geocoordinates_process_manager',
            function () use ($container) {
                return new ReplayFilteringEventListener(
                    new GeoCoordinatesProcessManager(
                        $container->get('event_command_bus'),
                        new CultureFeedAddressFactory(),
                        new NullLogger()
                    )
                );
            }
        );
    }
}

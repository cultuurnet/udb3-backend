<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer;

use CultuurNet\UDB3\Address\CultureFeed\CultureFeedAddressFactory;
use CultuurNet\UDB3\Address\Formatter\FullAddressFormatter;
use CultuurNet\UDB3\Address\Formatter\LocalityAddressFormatter;
use CultuurNet\UDB3\Broadway\EventHandling\ReplayFilteringEventListener;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Error\LoggerFactory;
use CultuurNet\UDB3\Error\LoggerName;
use CultuurNet\UDB3\Geocoding\GeocodingService;
use CultuurNet\UDB3\Organizer\CommandHandler\UpdateGeoCoordinatesFromAddressCommandHandler;
use CultuurNet\UDB3\Organizer\ProcessManager\GeoCoordinatesProcessManager;

final class OrganizerGeoCoordinatesServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            'organizer_geocoordinates_command_handler',
            'organizer_geocoordinates_process_manager',
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            'organizer_geocoordinates_command_handler',
            function () use ($container) {
                return new UpdateGeoCoordinatesFromAddressCommandHandler(
                    $container->get('organizer_repository'),
                    new FullAddressFormatter(),
                    new LocalityAddressFormatter(),
                    $container->get(GeocodingService::class)
                );
            }
        );

        $container->addShared(
            'organizer_geocoordinates_process_manager',
            function () use ($container) {
                return new ReplayFilteringEventListener(
                    new GeoCoordinatesProcessManager(
                        $container->get('event_command_bus'),
                        new CultureFeedAddressFactory(),
                        LoggerFactory::create($container, LoggerName::forService('geo-coordinates', 'organizer'))
                    )
                );
            }
        );
    }
}

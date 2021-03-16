<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Organizer;

use CultuurNet\UDB3\Broadway\EventHandling\ReplayFilteringEventListener;
use CultuurNet\UDB3\Address\CultureFeedAddressFactory;
use CultuurNet\UDB3\Address\DefaultAddressFormatter;
use CultuurNet\UDB3\Address\LocalityAddressFormatter;
use CultuurNet\UDB3\Organizer\CommandHandler\UpdateGeoCoordinatesFromAddressCommandHandler;
use CultuurNet\UDB3\Organizer\ProcessManager\GeoCoordinatesProcessManager;
use CultuurNet\UDB3\Silex\Error\LoggerFactory;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Silex\Application;
use Silex\ServiceProviderInterface;

class OrganizerGeoCoordinatesServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['organizer_geocoordinates_command_handler'] = $app->share(
            function (Application $app) {
                return new UpdateGeoCoordinatesFromAddressCommandHandler(
                    $app['organizer_repository'],
                    new DefaultAddressFormatter(),
                    new LocalityAddressFormatter(),
                    $app['geocoding_service']
                );
            }
        );

        $app['organizer_geocoordinates_process_manager'] = $app->share(
            function (Application $app) {
                return new ReplayFilteringEventListener(
                    new GeoCoordinatesProcessManager(
                        $app['event_command_bus'],
                        new CultureFeedAddressFactory(),
                        LoggerFactory::create($app, 'organizer-geocoordinates')
                    )
                );
            }
        );
    }


    public function boot(Application $app)
    {
    }
}

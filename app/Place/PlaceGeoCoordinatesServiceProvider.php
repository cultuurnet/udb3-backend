<?php

namespace CultuurNet\UDB3\Silex\Place;

use CultuurNet\UDB3\Broadway\EventHandling\ReplayFilteringEventListener;
use CultuurNet\UDB3\Address\CultureFeedAddressFactory;
use CultuurNet\UDB3\Address\DefaultAddressFormatter;
use CultuurNet\UDB3\Address\LocalityAddressFormatter;
use CultuurNet\UDB3\Place\GeoCoordinatesCommandHandler;
use CultuurNet\UDB3\Place\GeoCoordinatesProcessManager;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Silex\Application;
use Silex\ServiceProviderInterface;

class PlaceGeoCoordinatesServiceProvider implements ServiceProviderInterface
{
    /**
     * @param Application $app
     */
    public function register(Application $app)
    {
        $app['place_geocoordinates_command_handler'] = $app->share(
            function (Application $app) {
                $handler = new GeoCoordinatesCommandHandler(
                    $app['place_repository'],
                    new DefaultAddressFormatter(),
                    new LocalityAddressFormatter(),
                    $app['geocoding_service']
                );

                $handler->setLogger($app['logger.command_bus']);

                return $handler;
            }
        );

        $app['place_geocoordinates_log_handler'] = $app->share(
            function () {
                return new StreamHandler(__DIR__ . '/../log/place_geocoordinates.log');
            }
        );

        $app['place_geocoordinates_logger'] = $app->share(
            function (Application $app) {
                $logger = new Logger('place-geocoordinates');
                $logger->pushHandler($app['place_geocoordinates_log_handler']);
                return $logger;
            }
        );

        $app['place_geocoordinates_process_manager'] = $app->share(
            function (Application $app) {
                return new ReplayFilteringEventListener(
                    new GeoCoordinatesProcessManager(
                        $app['event_command_bus'],
                        new CultureFeedAddressFactory(),
                        $app['place_geocoordinates_logger']
                    )
                );
            }
        );
    }

    /**
     * @param Application $app
     */
    public function boot(Application $app)
    {
    }
}

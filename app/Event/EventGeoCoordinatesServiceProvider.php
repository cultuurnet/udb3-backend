<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Event;

use CultuurNet\UDB3\Broadway\EventHandling\ReplayFilteringEventListener;
use CultuurNet\UDB3\Address\CultureFeedAddressFactory;
use CultuurNet\UDB3\Address\DefaultAddressFormatter;
use CultuurNet\UDB3\Address\LocalityAddressFormatter;
use CultuurNet\UDB3\Event\GeoCoordinatesCommandHandler;
use CultuurNet\UDB3\Event\GeoCoordinatesProcessManager;
use CultuurNet\UDB3\Geocoding\GeocodingService;
use CultuurNet\UDB3\Silex\Error\LoggerFactory;
use CultuurNet\UDB3\Silex\Error\LoggerName;
use Psr\Log\NullLogger;
use Silex\Application;
use Silex\ServiceProviderInterface;

class EventGeoCoordinatesServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['event_geocoordinates_command_handler'] = $app->share(
            function (Application $app) {
                $handler = new GeoCoordinatesCommandHandler(
                    $app['event_repository'],
                    new DefaultAddressFormatter(),
                    new LocalityAddressFormatter(),
                    $app[GeocodingService::class]
                );

                $handler->setLogger(LoggerFactory::create($app, LoggerName::forService('geo-coordinates', 'event')));

                return $handler;
            }
        );

        $app['event_geocoordinates_process_manager'] = $app->share(
            function (Application $app) {
                return new ReplayFilteringEventListener(
                    new GeoCoordinatesProcessManager(
                        $app['event_command_bus'],
                        new CultureFeedAddressFactory(),
                        new NullLogger()
                    )
                );
            }
        );
    }


    public function boot(Application $app)
    {
    }
}

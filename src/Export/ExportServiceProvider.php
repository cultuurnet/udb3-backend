<?php

namespace CultuurNet\UDB3\Silex\Export;

use Broadway\CommandHandling\CommandBusInterface;
use CultuurNet\UDB3\EventExport\EventExportCommandHandler;
use CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\EventInfo\CultureFeedEventInfoService;
use CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\Promotion\EventOrganizerPromotionQueryFactory;
use Silex\Application;
use Silex\ServiceProviderInterface;

class ExportServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        // Set up the event export command bus.
        $app['resque_command_bus_factory']('event_export');

        // Set up the event export command handler.
        $app['event_export_command_handler'] = $app->share(
            function (Application $app) {
                $eventInfoService = new CultureFeedEventInfoService(
                    $app['uitpas'],
                    new EventOrganizerPromotionQueryFactory($app['clock'])
                );

                $eventInfoService->setLogger($app['logger.uitpas']);

                return new EventExportCommandHandler(
                    $app['event_export'],
                    $app['config']['prince']['binary'],
                    $eventInfoService,
                    $app['event_calendar_repository']
                );
            }
        );

        // Tie the event export command handler to the command bus.
        $app->extend(
            'event_export_command_bus_out',
            function (CommandBusInterface $commandBus, Application $app) {
                $commandBus->subscribe($app['event_export_command_handler']);
                return $commandBus;
            }
        );
    }

    public function boot(Application $app)
    {
    }
}

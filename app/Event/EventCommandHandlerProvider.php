<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Event;

use CultuurNet\UDB3\Event\CommandHandlers\CopyEventHandler;
use CultuurNet\UDB3\Event\CommandHandlers\RemoveThemeHandler;
use CultuurNet\UDB3\Event\CommandHandlers\UpdateAudienceHandler;
use CultuurNet\UDB3\Event\CommandHandlers\UpdateSubEventsHandler;
use CultuurNet\UDB3\Event\CommandHandlers\UpdateThemeHandler;
use CultuurNet\UDB3\Event\Productions\ProductionRepository;
use Silex\Application;
use Silex\ServiceProviderInterface;

final class EventCommandHandlerProvider implements ServiceProviderInterface
{
    public function register(Application $app): void
    {
        $app[UpdateSubEventsHandler::class] = $app->share(
            fn (Application $application) => new UpdateSubEventsHandler($app['event_repository'])
        );

        $app[UpdateThemeHandler::class] = $app->share(
            fn (Application $application) => new UpdateThemeHandler($app['event_repository'])
        );

        $app[RemoveThemeHandler::class] = $app->share(
            fn (Application $application) => new RemoveThemeHandler($app['event_repository'])
        );

        $app[UpdateAudienceHandler::class] = $app->share(
            fn (Application $application) => new UpdateAudienceHandler($app['event_repository'])
        );

        $app[CopyEventHandler::class] = $app->share(
            fn (Application $application) => new CopyEventHandler(
                $app['event_repository'],
                $app[ProductionRepository::class]
            )
        );
    }

    public function boot(Application $app): void
    {
    }
}

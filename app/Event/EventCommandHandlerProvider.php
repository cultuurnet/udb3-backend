<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Event;

use CultuurNet\UDB3\Event\CommandHandlers\UpdateAudienceHandler;
use Silex\Application;
use Silex\ServiceProviderInterface;

final class EventCommandHandlerProvider implements ServiceProviderInterface
{
    public function register(Application $app): void
    {
        $app[RemoveThemeHandler::class] = $app->share(
            fn (Application $application) => new RemoveThemeHandler($app['event_repository'])
        );

        $app[UpdateAudienceHandler::class] = $app->share(
            fn (Application $application) => new UpdateAudienceHandler($app['event_repository'])
        );
    }

    public function boot(Application $app): void
    {
    }
}

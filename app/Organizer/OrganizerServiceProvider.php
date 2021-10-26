<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Organizer;

use CultuurNet\UDB3\Http\Organizer\UpdateTitleRequestHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\AddLabelHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\ImportLabelsHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\RemoveLabelHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\UpdateTitleHandler;
use CultuurNet\UDB3\Silex\Labels\LabelServiceProvider;
use Silex\Application;
use Silex\ServiceProviderInterface;

final class OrganizerServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app): void
    {
        $app[AddLabelHandler::class] = $app->share(
            function (Application $app) {
                return new AddLabelHandler(
                    $app['organizer_repository'],
                    $app[LabelServiceProvider::JSON_READ_REPOSITORY],
                    $app['labels.constraint_aware_service']
                );
            }
        );

        $app[RemoveLabelHandler::class] = $app->share(
            function (Application $app) {
                return new RemoveLabelHandler(
                    $app['organizer_repository'],
                    $app[LabelServiceProvider::JSON_READ_REPOSITORY]
                );
            }
        );

        $app[ImportLabelsHandler::class] = $app->share(
            function (Application $app) {
                return new ImportLabelsHandler(
                    $app['organizer_repository'],
                    $app['labels.constraint_aware_service']
                );
            }
        );

        $app[UpdateTitleRequestHandler::class] = $app->share(
            fn (Application $application) => new UpdateTitleRequestHandler($app['event_command_bus'])
        );

        $app[UpdateTitleHandler::class] = $app->share(
            fn (Application $application) => new UpdateTitleHandler($app['organizer_repository'])
        );
    }

    public function boot(Application $app): void
    {
    }
}

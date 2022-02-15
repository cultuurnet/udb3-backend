<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Organizer;

use CultuurNet\UDB3\Event\EventOrganizerRelationService;
use CultuurNet\UDB3\Organizer\CommandHandler\AddImageHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\AddLabelHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\DeleteDescriptionHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\DeleteOrganizerHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\ImportLabelsHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\RemoveAddressHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\RemoveImageHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\RemoveLabelHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\UpdateAddressHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\UpdateContactPointHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\UpdateDescriptionHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\UpdateImageHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\UpdateMainImageHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\UpdateTitleHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\UpdateWebsiteHandler;
use CultuurNet\UDB3\Place\PlaceOrganizerRelationService;
use CultuurNet\UDB3\Silex\Labels\LabelServiceProvider;
use Silex\Application;
use Silex\ServiceProviderInterface;

class OrganizerCommandHandlerProvider implements ServiceProviderInterface
{
    public function register(Application $app): void
    {
        $app[DeleteOrganizerHandler::class] = $app->share(
            fn (Application $application) => new DeleteOrganizerHandler(
                $app['organizer_repository'],
                $app[EventOrganizerRelationService::class],
                $app[PlaceOrganizerRelationService::class]
            )
        );

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
                    $app['labels.constraint_aware_service'],
                    $app[LabelServiceProvider::JSON_READ_REPOSITORY],
                    $app['labels.labels_locked_for_import_repository'],
                    $app['current_user_id']
                );
            }
        );

        $app[UpdateTitleHandler::class] = $app->share(
            fn (Application $application) => new UpdateTitleHandler($app['organizer_repository'])
        );

        $app[UpdateDescriptionHandler::class] = $app->share(
            fn (Application $application) => new UpdateDescriptionHandler($app['organizer_repository'])
        );

        $app[DeleteDescriptionHandler::class] = $app->share(
            fn (Application $application) => new DeleteDescriptionHandler($app['organizer_repository'])
        );

        $app[UpdateAddressHandler::class] = $app->share(
            fn (Application $application) => new UpdateAddressHandler($app['organizer_repository'])
        );

        $app[RemoveAddressHandler::class] = $app->share(
            fn (Application $application) => new RemoveAddressHandler($app['organizer_repository'])
        );

        $app[UpdateWebsiteHandler::class] = $app->share(
            fn (Application $application) => new UpdateWebsiteHandler($app['organizer_repository'])
        );

        $app[UpdateContactPointHandler::class] = $app->share(
            fn (Application $application) => new UpdateContactPointHandler($app['organizer_repository'])
        );

        $app[AddImageHandler::class] = $app->share(
            fn (Application $application) => new AddImageHandler($app['organizer_repository'])
        );

        $app[UpdateMainImageHandler::class] = $app->share(
            fn (Application $application) => new UpdateMainImageHandler($app['organizer_repository'])
        );

        $app[UpdateImageHandler::class] = $app->share(
            fn (Application $application) => new UpdateImageHandler($app['organizer_repository'])
        );

        $app[RemoveImageHandler::class] = $app->share(
            fn (Application $application) => new RemoveImageHandler($app['organizer_repository'])
        );
    }

    public function boot(Application $app): void
    {
    }
}

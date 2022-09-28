<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Event;

use CultuurNet\UDB3\Event\ReadModel\Relations\EventRelationsRepository;
use CultuurNet\UDB3\Http\Event\CopyEventRequestHandler;
use CultuurNet\UDB3\Http\Event\DeleteOnlineUrlRequestHandler;
use CultuurNet\UDB3\Http\Event\DeleteThemeRequestHandler;
use CultuurNet\UDB3\Http\Event\ImportEventRequestHandler;
use CultuurNet\UDB3\Http\Event\LegacyEventRequestBodyParser;
use CultuurNet\UDB3\Http\Event\UpdateAttendanceModeRequestHandler;
use CultuurNet\UDB3\Http\Event\UpdateAudienceRequestHandler;
use CultuurNet\UDB3\Http\Event\UpdateLocationRequestHandler;
use CultuurNet\UDB3\Http\Event\UpdateMajorInfoRequestHandler;
use CultuurNet\UDB3\Http\Event\UpdateOnlineUrlRequestHandler;
use CultuurNet\UDB3\Http\Event\UpdateSubEventsRequestHandler;
use CultuurNet\UDB3\Http\Event\UpdateThemeRequestHandler;
use CultuurNet\UDB3\Http\Event\OnlineLocationPolyfillRequestBodyParser;
use CultuurNet\UDB3\Http\Import\ImportPriceInfoRequestBodyParser;
use CultuurNet\UDB3\Http\Import\ImportTermRequestBodyParser;
use CultuurNet\UDB3\Http\Import\RemoveEmptyArraysRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\CombinedRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\ImagesPropertyPolyfillRequestBodyParser;
use CultuurNet\UDB3\Model\Import\Event\EventCategoryResolver;
use CultuurNet\UDB3\Model\Serializer\Event\EventDenormalizer;
use Ramsey\Uuid\UuidFactory;
use Silex\Application;
use Silex\ServiceProviderInterface;

final class EventRequestHandlerServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app): void
    {
        $app[ImportEventRequestHandler::class] = $app->share(
            fn (Application $app) => new ImportEventRequestHandler(
                $app['event_repository'],
                $app['uuid_generator'],
                $app['event_iri_generator'],
                new EventDenormalizer(),
                new CombinedRequestBodyParser(
                    new LegacyEventRequestBodyParser($app['place_iri_generator']),
                    RemoveEmptyArraysRequestBodyParser::createForEvents(),
                    new ImportTermRequestBodyParser(new EventCategoryResolver()),
                    new ImportPriceInfoRequestBodyParser($app['config']['base_price_translations']),
                    ImagesPropertyPolyfillRequestBodyParser::createForEvents(
                        $app['media_object_iri_generator'],
                        $app['media_object_repository']
                    ),
                    new OnlineLocationPolyfillRequestBodyParser($app['place_iri_generator'])
                ),
                $app['event_command_bus'],
                $app['import_image_collection_factory'],
                $app['place_jsonld_repository']
            )
        );

        $app[UpdateLocationRequestHandler::class] = $app->share(
            fn (Application $app) => new UpdateLocationRequestHandler(
                $app['event_command_bus'],
                $app['place_jsonld_repository']
            )
        );

        $app[UpdateSubEventsRequestHandler::class] = $app->share(
            fn (Application $app) => new UpdateSubEventsRequestHandler($app['event_command_bus'])
        );

        $app[UpdateThemeRequestHandler::class] = $app->share(
            fn (Application $app) => new UpdateThemeRequestHandler($app['event_command_bus'])
        );

        $app[DeleteThemeRequestHandler::class] = $app->share(
            fn (Application $app) => new DeleteThemeRequestHandler($app['event_command_bus'])
        );

        $app[UpdateAttendanceModeRequestHandler::class] = $app->share(
            fn (Application $app) => new UpdateAttendanceModeRequestHandler(
                $app['event_command_bus'],
                $app[EventRelationsRepository::class]
            )
        );

        $app[UpdateOnlineUrlRequestHandler::class] = $app->share(
            fn (Application $app) => new UpdateOnlineUrlRequestHandler($app['event_command_bus'])
        );

        $app[DeleteOnlineUrlRequestHandler::class] = $app->share(
            fn (Application $app) => new DeleteOnlineUrlRequestHandler($app['event_command_bus'])
        );

        $app[UpdateAudienceRequestHandler::class] = $app->share(
            fn (Application $app) => new UpdateAudienceRequestHandler($app['event_command_bus'])
        );

        $app[CopyEventRequestHandler::class] = $app->share(
            fn (Application $app) => new CopyEventRequestHandler(
                $app['event_command_bus'],
                new UuidFactory(),
                $app['event_iri_generator']
            )
        );

        $app[UpdateMajorInfoRequestHandler::class] = $app->share(
            fn (Application $app) => new UpdateMajorInfoRequestHandler($app['event_command_bus'])
        );
    }

    public function boot(Application $app): void
    {
    }
}

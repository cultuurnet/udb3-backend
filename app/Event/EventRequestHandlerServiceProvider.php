<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use Broadway\UuidGenerator\Rfc4122\Version4Generator;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Event\ReadModel\Relations\EventRelationsRepository;
use CultuurNet\UDB3\Http\Event\CopyEventRequestHandler;
use CultuurNet\UDB3\Http\Event\DeleteOnlineUrlRequestHandler;
use CultuurNet\UDB3\Http\Event\DeleteThemeRequestHandler;
use CultuurNet\UDB3\Http\Event\ImportEventRequestHandler;
use CultuurNet\UDB3\Http\Event\LegacyEventRequestBodyParser;
use CultuurNet\UDB3\Http\Event\OnlineLocationPolyfillRequestBodyParser;
use CultuurNet\UDB3\Http\Event\UpdateAttendanceModeRequestHandler;
use CultuurNet\UDB3\Http\Event\UpdateAudienceRequestHandler;
use CultuurNet\UDB3\Http\Event\UpdateLocationRequestHandler;
use CultuurNet\UDB3\Http\Event\UpdateMajorInfoRequestHandler;
use CultuurNet\UDB3\Http\Event\UpdateOnlineUrlRequestHandler;
use CultuurNet\UDB3\Http\Event\UpdateSubEventsRequestHandler;
use CultuurNet\UDB3\Http\Event\UpdateThemeRequestHandler;
use CultuurNet\UDB3\Http\Import\ImportPriceInfoRequestBodyParser;
use CultuurNet\UDB3\Http\Import\ImportTermRequestBodyParser;
use CultuurNet\UDB3\Http\Import\RemoveEmptyArraysRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\CombinedRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\ImagesPropertyPolyfillRequestBodyParser;
use CultuurNet\UDB3\Model\Import\Event\EventCategoryResolver;
use CultuurNet\UDB3\Model\Serializer\Event\EventDenormalizer;
use CultuurNet\UDB3\Model\ValueObject\Identity\UuidFactory\UuidGenerationFactory;

final class EventRequestHandlerServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            ImportEventRequestHandler::class,
            UpdateLocationRequestHandler::class,
            UpdateSubEventsRequestHandler::class,
            UpdateThemeRequestHandler::class,
            DeleteThemeRequestHandler::class,
            UpdateAttendanceModeRequestHandler::class,
            UpdateOnlineUrlRequestHandler::class,
            DeleteOnlineUrlRequestHandler::class,
            UpdateAudienceRequestHandler::class,
            CopyEventRequestHandler::class,
            UpdateMajorInfoRequestHandler::class,
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            ImportEventRequestHandler::class,
            function () use ($container): ImportEventRequestHandler {
                return new ImportEventRequestHandler(
                    $container->get('event_repository'),
                    new Version4Generator(),
                    $container->get('event_iri_generator'),
                    new EventDenormalizer(),
                    new CombinedRequestBodyParser(
                        new LegacyEventRequestBodyParser($container->get('place_iri_generator')),
                        RemoveEmptyArraysRequestBodyParser::createForEvents(),
                        new ImportTermRequestBodyParser(new EventCategoryResolver()),
                        new ImportPriceInfoRequestBodyParser($container->get('config')['base_price_translations']),
                        ImagesPropertyPolyfillRequestBodyParser::createForEvents(
                            $container->get('media_object_iri_generator'),
                            $container->get('media_object_repository')
                        ),
                        new OnlineLocationPolyfillRequestBodyParser($container->get('place_iri_generator'))
                    ),
                    $container->get('event_command_bus'),
                    $container->get('import_image_collection_factory'),
                    $container->get('place_jsonld_repository'),
                    $container->get('organizer_jsonld_repository')
                );
            }
        );

        $container->addShared(
            UpdateLocationRequestHandler::class,
            function () use ($container): UpdateLocationRequestHandler {
                return new UpdateLocationRequestHandler(
                    $container->get('event_command_bus'),
                    $container->get('place_jsonld_repository'),
                );
            }
        );

        $container->addShared(
            UpdateSubEventsRequestHandler::class,
            function () use ($container): UpdateSubEventsRequestHandler {
                return new UpdateSubEventsRequestHandler($container->get('event_command_bus'));
            }
        );

        $container->addShared(
            UpdateThemeRequestHandler::class,
            function () use ($container): UpdateThemeRequestHandler {
                return new UpdateThemeRequestHandler($container->get('event_command_bus'));
            }
        );

        $container->addShared(
            DeleteThemeRequestHandler::class,
            function () use ($container): DeleteThemeRequestHandler {
                return new DeleteThemeRequestHandler($container->get('event_command_bus'));
            }
        );

        $container->addShared(
            UpdateAttendanceModeRequestHandler::class,
            function () use ($container): UpdateAttendanceModeRequestHandler {
                return new UpdateAttendanceModeRequestHandler(
                    $container->get('event_command_bus'),
                    $container->get(EventRelationsRepository::class),
                );
            }
        );

        $container->addShared(
            UpdateOnlineUrlRequestHandler::class,
            function () use ($container): UpdateOnlineUrlRequestHandler {
                return new UpdateOnlineUrlRequestHandler($container->get('event_command_bus'));
            }
        );

        $container->addShared(
            DeleteOnlineUrlRequestHandler::class,
            function () use ($container): DeleteOnlineUrlRequestHandler {
                return new DeleteOnlineUrlRequestHandler($container->get('event_command_bus'));
            }
        );

        $container->addShared(
            UpdateAudienceRequestHandler::class,
            function () use ($container): UpdateAudienceRequestHandler {
                return new UpdateAudienceRequestHandler($container->get('event_command_bus'));
            }
        );

        $container->addShared(
            CopyEventRequestHandler::class,
            function () use ($container): CopyEventRequestHandler {
                return new CopyEventRequestHandler(
                    $container->get('event_command_bus'),
                    new UuidGenerationFactory(),
                    $container->get('event_iri_generator'),
                );
            }
        );

        $container->addShared(
            UpdateMajorInfoRequestHandler::class,
            function () use ($container): UpdateMajorInfoRequestHandler {
                return new UpdateMajorInfoRequestHandler($container->get('event_command_bus'));
            }
        );
    }
}

<?php

use Broadway\CommandHandling\CommandBus;
use Broadway\EventHandling\EventBus;
use CultuurNet\UDB3\CalendarFactory;
use CultuurNet\UDB3\Clock\SystemClock;
use CultuurNet\UDB3\Event\CommandHandlers\CopyEventHandler;
use CultuurNet\UDB3\Event\CommandHandlers\DeleteOnlineUrlHandler;
use CultuurNet\UDB3\Event\CommandHandlers\RemoveThemeHandler;
use CultuurNet\UDB3\Event\CommandHandlers\UpdateAttendanceModeHandler;
use CultuurNet\UDB3\Event\CommandHandlers\UpdateAudienceHandler;
use CultuurNet\UDB3\Event\CommandHandlers\UpdateOnlineUrlHandler;
use CultuurNet\UDB3\Event\CommandHandlers\UpdateSubEventsHandler;
use CultuurNet\UDB3\Event\CommandHandlers\UpdateThemeHandler;
use CultuurNet\UDB3\Event\CommandHandlers\UpdateUiTPASPricesHandler;
use CultuurNet\UDB3\Event\Productions\ProductionCommandHandler;
use CultuurNet\UDB3\Event\ReadModel\Relations\EventRelationsRepository;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\EventSourcing\DBAL\AggregateAwareDBALEventStore;
use CultuurNet\UDB3\EventSourcing\DBAL\UniqueDBALEventStoreDecorator;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\Doctrine\DBALReadRepository;
use CultuurNet\UDB3\Log\SocketIOEmitterHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\AddLabelHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\AddVideoHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\ChangeOwnerHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\DeleteOfferHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\DeleteOrganizerHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\DeleteVideoHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\ImportLabelsHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\ImportVideosHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\RemoveLabelHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\UpdateAvailableFromHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\UpdateBookingAvailabilityHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\UpdateCalendarHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\UpdateFacilitiesHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\UpdateOrganizerHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\UpdatePriceInfoHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\UpdateStatusHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\UpdateTitleHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\UpdateTypeHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\UpdateVideoHandler;
use CultuurNet\UDB3\Offer\OfferLocator;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\CdbXmlContactInfoImporter;
use CultuurNet\UDB3\Organizer\CommandHandler\AddImageHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\DeleteDescriptionHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\ImportImagesHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\RemoveAddressHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\RemoveImageHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\UpdateAddressHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\UpdateContactPointHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\UpdateDescriptionHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\UpdateImageHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\UpdateMainImageHandler;
use CultuurNet\UDB3\Organizer\CommandHandler\UpdateWebsiteHandler;
use CultuurNet\UDB3\Organizer\WebsiteNormalizer;
use CultuurNet\UDB3\Organizer\WebsiteUniqueConstraintService;
use CultuurNet\UDB3\Place\Canonical\CanonicalService;
use CultuurNet\UDB3\Place\Canonical\DBALDuplicatePlaceRepository;
use CultuurNet\UDB3\Place\LocalPlaceService;
use CultuurNet\UDB3\Place\ReadModel\Relations\PlaceRelationsRepository;
use CultuurNet\UDB3\AggregateType;
use CultuurNet\UDB3\Silex\AMQP\AMQPConnectionServiceProvider;
use CultuurNet\UDB3\Silex\AMQP\AMQPPublisherServiceProvider;
use CultuurNet\UDB3\ApiName;
use CultuurNet\UDB3\Silex\Auth0\Auth0ServiceProvider;
use CultuurNet\UDB3\Silex\Authentication\AuthServiceProvider;
use CultuurNet\UDB3\Silex\CommandHandling\LazyLoadingCommandBus;
use CultuurNet\UDB3\Silex\Container\HybridContainerApplication;
use CultuurNet\UDB3\Silex\Container\PimplePSRContainerBridge;
use CultuurNet\UDB3\Silex\CultureFeed\CultureFeedServiceProvider;
use CultuurNet\UDB3\Silex\Curators\CuratorsServiceProvider;
use CultuurNet\UDB3\Error\LoggerFactory;
use CultuurNet\UDB3\Error\LoggerName;
use CultuurNet\UDB3\Silex\Error\SentryServiceProvider;
use CultuurNet\UDB3\Silex\Event\EventCommandHandlerProvider;
use CultuurNet\UDB3\Silex\Event\EventHistoryServiceProvider;
use CultuurNet\UDB3\Silex\Event\EventJSONLDServiceProvider;
use CultuurNet\UDB3\Silex\Event\EventRequestHandlerServiceProvider;
use CultuurNet\UDB3\Silex\EventBus\EventBusServiceProvider;
use CultuurNet\UDB3\Silex\Jobs\JobsServiceProvider;
use CultuurNet\UDB3\Silex\Labels\LabelServiceProvider;
use CultuurNet\UDB3\Silex\Media\ImageStorageProvider;
use CultuurNet\UDB3\Silex\Metadata\MetadataServiceProvider;
use CultuurNet\UDB3\Silex\Organizer\OrganizerJSONLDServiceProvider;
use CultuurNet\UDB3\Silex\Organizer\OrganizerCommandHandlerProvider;
use CultuurNet\UDB3\Silex\Organizer\OrganizerRequestHandlerServiceProvider;
use CultuurNet\UDB3\Silex\Place\PlaceHistoryServiceProvider;
use CultuurNet\UDB3\Silex\Place\PlaceJSONLDServiceProvider;
use CultuurNet\UDB3\Silex\Place\PlaceRequestHandlerServiceProvider;
use CultuurNet\UDB3\Silex\Role\RoleRequestHandlerServiceProvider;
use CultuurNet\UDB3\Silex\Role\UserPermissionsServiceProvider;
use CultuurNet\UDB3\Silex\Search\Sapi3SearchServiceProvider;
use CultuurNet\UDB3\Silex\Security\GeneralSecurityServiceProvider;
use CultuurNet\UDB3\Silex\Security\OrganizerSecurityServiceProvider;
use CultuurNet\UDB3\Silex\Term\TermServiceProvider;
use CultuurNet\UDB3\Silex\UiTPASService\UiTPASServiceEventServiceProvider;
use CultuurNet\UDB3\Silex\UiTPASService\UiTPASServiceLabelsServiceProvider;
use CultuurNet\UDB3\Silex\UiTPASService\UiTPASServiceOrganizerServiceProvider;
use CultuurNet\UDB3\Silex\Yaml\YamlConfigServiceProvider;
use CultuurNet\UDB3\User\Auth0UserIdentityResolver;
use Http\Adapter\Guzzle7\Client;
use League\Container\Container;
use League\Container\ReflectionContainer;
use Monolog\Logger;
use Silex\Application;
use SocketIO\Emitter;
use CultuurNet\UDB3\StringLiteral;

date_default_timezone_set('Europe/Brussels');

/**
 * Set up a PSR-11 container using league/container. The goal is for this container to replace the Silex Application
 * object (a Pimple container).
 * We inject this new PSR container into the Silex application (extended via HybridContainerApplication) so that Silex
 * service definitions can fetch services from the PSR container (if they exist there) instead of the Silex container.
 * We then wrap the Silex container in a decorator that makes it PSR-11 compatible and set that as a delegate on the
 * league container so that service definitions in the league container can fetch services from the Silex container if
 * they do not exist in the league container.
 * Lastly we set a ReflectionContainer as a second delegate on the league container to enable auto-wiring in the league
 * container. Because the Silex container also looks up missing services in the league container, it also gets auto-
 * wiring this way.
 */
$container = new Container();
$app = new HybridContainerApplication($container);
$container->delegate(new PimplePSRContainerBridge($app));
$container->delegate(new ReflectionContainer(true));

$app['api_name'] = defined('API_NAME') ? API_NAME : ApiName::UNKNOWN;

if (!isset($udb3ConfigLocation)) {
    $udb3ConfigLocation = __DIR__;
}
$app->register(new YamlConfigServiceProvider($udb3ConfigLocation . '/config.yml'));

$app['system_user_id'] = $app::share(
    function () {
        return '00000000-0000-0000-0000-000000000000';
    }
);

// Add the system user to the list of god users.
$app['config'] = array_merge_recursive(
    $app['config'],
    [
        'user_permissions' => [
            'allow_all' => [
                $app['system_user_id']
            ],
        ],
    ]
);

$app['debug'] = $app['config']['debug'] ?? false;

$app['event_store_factory'] = $app->protect(
    function (AggregateType $aggregateType) use ($app) {
        return new AggregateAwareDBALEventStore(
            $app['dbal_connection'],
            $app['eventstore_payload_serializer'],
            new \Broadway\Serializer\SimpleInterfaceSerializer(),
            'event_store',
            $aggregateType
        );
    }
);

$app->register(new SentryServiceProvider());

$app->register(new \CultuurNet\UDB3\Silex\SavedSearches\SavedSearchesServiceProvider());

$app->register(new \CultuurNet\UDB3\Silex\CommandHandling\CommandBusServiceProvider());
$app->register(new EventBusServiceProvider());

/**
 * CultureFeed services.
 */
$app->register(
    new CultureFeedServiceProvider(),
    [
        'culturefeed.endpoint' => $app['config']['uitid']['base_url'],
        'culturefeed.consumer.key' => $app['config']['uitid']['consumer']['key'],
        'culturefeed.consumer.secret' => $app['config']['uitid']['consumer']['secret'],
    ]
);

/**
 * Mailing service.
 */
$app->register(new Silex\Provider\SwiftmailerServiceProvider());
$app['swiftmailer.use_spool'] = false;
if ($app['config']['swiftmailer.options']) {
    $app['swiftmailer.options'] = $app['config']['swiftmailer.options'];
}

$app['timezone'] = $app->share(
    function (Application $app) {
        $timezoneName = empty($app['config']['timezone']) ? 'Europe/Brussels' : $app['config']['timezone'];

        return new DateTimeZone($timezoneName);
    }
);

$app['clock'] = $app->share(
    function (Application $app) {
        return new SystemClock(
            $app['timezone']
        );
    }
);

$app['uuid_generator'] = $app->share(
    function () {
        return new \Broadway\UuidGenerator\Rfc4122\Version4Generator();
    }
);

$app['event_iri_generator'] = $app->share(
    function ($app) {
        return new CallableIriGenerator(
            function ($cdbid) use ($app) {
                return $app['config']['url'] . '/event/' . $cdbid;
            }
        );
    }
);

$app->register(new GeneralSecurityServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\Security\OfferSecurityServiceProvider());
$app->register(new OrganizerSecurityServiceProvider());

$app['cache'] = $app->share(
    function (Application $app) {
        $parameters = $app['config']['cache']['redis'];

        return function ($cacheType) use ($parameters) {
            $redisClient = new Predis\Client(
                $parameters,
                [
                    'prefix' => $cacheType . '_',
                ]
            );

            return new Doctrine\Common\Cache\PredisCache($redisClient);
        };
    }
);


$app['dbal_connection'] = $app->share(
    function ($app) {
        $eventManager = new \Doctrine\Common\EventManager();
        $sqlMode = 'NO_ENGINE_SUBSTITUTION,STRICT_ALL_TABLES';
        $query = "SET SESSION sql_mode = '{$sqlMode}'";
        $eventManager->addEventSubscriber(
            new \Doctrine\DBAL\Event\Listeners\SQLSessionInit($query)
        );

        $connection = \Doctrine\DBAL\DriverManager::getConnection(
            $app['config']['database'],
            null,
            $eventManager
        );

        return $connection;
    }
);

$app['dbal_connection:keepalive'] = $app->protect(
    function (Application $app) {
        /** @var \Doctrine\DBAL\Connection $db */
        $db = $app['dbal_connection'];

        $db->query('SELECT 1')->execute();
    }
);

$app['dbal_event_store'] = $app->share(
    function ($app) {
        return $app['event_store_factory'](AggregateType::event());
    }
);

$app['event_store'] = $app->share(
    function ($app) {
        return new \CultuurNet\UDB3\EventSourcing\CopyAwareEventStoreDecorator($app['dbal_event_store']);
    }
);

$app['calendar_factory'] = $app->share(
    function () {
        return new CalendarFactory();
    }
);

$app['cdbxml_contact_info_importer'] = $app->share(
    function () {
        return new CdbXmlContactInfoImporter();
    }
);

$app->register(new EventJSONLDServiceProvider());

$app['event_calendar_repository'] = $app->share(
    function ($app) {
        return new \CultuurNet\UDB3\Event\ReadModel\Calendar\CacheCalendarRepository(
            $app['event_calendar_cache']
        );
    }
);

$app['event_calendar_cache'] = $app->share(
    function (Application $app) {
        return $app['cache']('event_calendar');
    }
);

$app['event_calendar_projector'] = $app->share(
    function ($app) {
        return new \CultuurNet\UDB3\Event\ReadModel\Calendar\EventCalendarProjector(
            $app['event_calendar_repository']
        );
    }
);

$app['events_locator_event_stream_decorator'] = $app->share(
    function (Application $app) {
        return new OfferLocator($app['event_iri_generator']);
    }
);

$app['event_repository'] = $app->share(
    function ($app) {
        $repository = new \CultuurNet\UDB3\Event\EventRepository(
            $app['event_store'],
            $app[EventBus::class],
            [
                $app['event_stream_metadata_enricher'],
                $app['events_locator_event_stream_decorator']
            ]
        );

        return $repository;
    }
);

$app['logger_factory.resque_worker'] = $app::protect(
    function ($queueName) use ($app) {
        $redisConfig = [
            'host' => '127.0.0.1',
            'port' => 6379,
        ];
        if (extension_loaded('redis')) {
            $redis = new Redis();
            $redis->connect(
                $redisConfig['host'],
                $redisConfig['port']
            );
        } else {
            $redis = new Predis\Client(
                [
                    'host' => $redisConfig['host'],
                    'port' => $redisConfig['port']
                ]
            );
            $redis->connect();
        }
        $socketIOHandler = new SocketIOEmitterHandler(new Emitter($redis), Logger::INFO);

        return LoggerFactory::create($app->getLeagueContainer(), LoggerName::forResqueWorker($queueName), [$socketIOHandler]);
    }
);

$subscribeCoreCommandHandlers = function (CommandBus $commandBus, Application $app): CommandBus {
    $subscribe = function (CommandBus $commandBus) use ($app) {
        $commandBus->subscribe(
            new \CultuurNet\UDB3\Event\EventCommandHandler(
                $app['event_repository'],
                $app['organizer_repository'],
                $app['media_manager']
            )
        );

        $commandBus->subscribe($app['saved_searches_command_handler']);

        $commandBus->subscribe(
            new \CultuurNet\UDB3\Place\CommandHandler(
                $app['place_repository'],
                $app['organizer_repository'],
                $app['media_manager']
            )
        );

        $commandBus->subscribe(
            new \CultuurNet\UDB3\Role\CommandHandler($app['real_role_repository'])
        );

        $commandBus->subscribe($app['media_manager']);
        $commandBus->subscribe($app['place_geocoordinates_command_handler']);
        $commandBus->subscribe($app['event_geocoordinates_command_handler']);
        $commandBus->subscribe($app['organizer_geocoordinates_command_handler']);
        $commandBus->subscribe($app[ProductionCommandHandler::class]);

        // Offer command handlers
        // @todo can we auto-discover these and register them automatically?
        // @see https://jira.uitdatabank.be/browse/III-4176
        $commandBus->subscribe($app[UpdateTitleHandler::class]);
        $commandBus->subscribe($app[UpdateAvailableFromHandler::class]);
        $commandBus->subscribe($app[UpdateCalendarHandler::class]);
        $commandBus->subscribe($app[UpdateStatusHandler::class]);
        $commandBus->subscribe($app[UpdateBookingAvailabilityHandler::class]);
        $commandBus->subscribe($app[UpdateTypeHandler::class]);
        $commandBus->subscribe($app[UpdateFacilitiesHandler::class]);
        $commandBus->subscribe($app[ChangeOwnerHandler::class]);
        $commandBus->subscribe($app[AddLabelHandler::class]);
        $commandBus->subscribe($app[RemoveLabelHandler::class]);
        $commandBus->subscribe($app[ImportLabelsHandler::class]);
        $commandBus->subscribe($app[AddVideoHandler::class]);
        $commandBus->subscribe($app[UpdateVideoHandler::class]);
        $commandBus->subscribe($app[DeleteVideoHandler::class]);
        $commandBus->subscribe($app[ImportVideosHandler::class]);
        $commandBus->subscribe($app[DeleteOfferHandler::class]);
        $commandBus->subscribe($app[UpdatePriceInfoHandler::class]);
        $commandBus->subscribe($app[UpdateOrganizerHandler::class]);
        $commandBus->subscribe($app[DeleteOrganizerHandler::class]);

        // Event command handlers
        $commandBus->subscribe($app[UpdateSubEventsHandler::class]);
        $commandBus->subscribe($app[UpdateThemeHandler::class]);
        $commandBus->subscribe($app[RemoveThemeHandler::class]);
        $commandBus->subscribe($app[UpdateAttendanceModeHandler::class]);
        $commandBus->subscribe($app[UpdateOnlineUrlHandler::class]);
        $commandBus->subscribe($app[DeleteOnlineUrlHandler::class]);
        $commandBus->subscribe($app[UpdateAudienceHandler::class]);
        $commandBus->subscribe($app[UpdateUiTPASPricesHandler::class]);
        $commandBus->subscribe($app[CopyEventHandler::class]);

        // Organizer command handlers
        $commandBus->subscribe($app[\CultuurNet\UDB3\Organizer\CommandHandler\DeleteOrganizerHandler::class]);
        $commandBus->subscribe($app[\CultuurNet\UDB3\Organizer\CommandHandler\AddLabelHandler::class]);
        $commandBus->subscribe($app[\CultuurNet\UDB3\Organizer\CommandHandler\RemoveLabelHandler::class]);
        $commandBus->subscribe($app[\CultuurNet\UDB3\Organizer\CommandHandler\ImportLabelsHandler::class]);
        $commandBus->subscribe($app[\CultuurNet\UDB3\Organizer\CommandHandler\UpdateTitleHandler::class]);
        $commandBus->subscribe($app[UpdateDescriptionHandler::class]);
        $commandBus->subscribe($app[DeleteDescriptionHandler::class]);
        $commandBus->subscribe($app[UpdateAddressHandler::class]);
        $commandBus->subscribe($app[RemoveAddressHandler::class]);
        $commandBus->subscribe($app[UpdateWebsiteHandler::class]);
        $commandBus->subscribe($app[UpdateContactPointHandler::class]);
        $commandBus->subscribe($app[AddImageHandler::class]);
        $commandBus->subscribe($app[UpdateMainImageHandler::class]);
        $commandBus->subscribe($app[UpdateImageHandler::class]);
        $commandBus->subscribe($app[RemoveImageHandler::class]);
        $commandBus->subscribe($app[ImportImagesHandler::class]);
        $commandBus->subscribe($app[\CultuurNet\UDB3\Organizer\CommandHandler\ChangeOwnerHandler::class]);

        $commandBus->subscribe($app[LabelServiceProvider::COMMAND_HANDLER]);
    };

    if ($commandBus instanceof LazyLoadingCommandBus) {
        $commandBus->beforeFirstDispatch($subscribe);
    } else {
        $subscribe($commandBus);
    }

    return $commandBus;
};

$app->extend('event_command_bus', $subscribeCoreCommandHandlers);

/** Production */


/** Place **/

$app['place_iri_generator'] = $app->share(
    function ($app) {
        return new CallableIriGenerator(
            function ($cdbid) use ($app) {
                return $app['config']['url'] . '/place/' . $cdbid;
            }
        );
    }
);

$app->register(new PlaceJSONLDServiceProvider());

$app['place_store'] = $app->share(
    function ($app) {
        return $app['event_store_factory'](AggregateType::place());
    }
);

$app['places_locator_event_stream_decorator'] = $app->share(
    function (Application $app) {
        return new OfferLocator($app['place_iri_generator']);
    }
);

$app['place_repository'] = $app->share(
    function (Application $app) {
        $repository = new \CultuurNet\UDB3\Place\PlaceRepository(
            $app['place_store'],
            $app[EventBus::class],
            array(
                $app['event_stream_metadata_enricher'],
                $app['places_locator_event_stream_decorator']
            )
        );

        return $repository;
    }
);

$app['place_service'] = $app->share(
    function ($app) {
        return new LocalPlaceService(
            $app['place_jsonld_repository'],
            $app['place_repository'],
            $app[PlaceRelationsRepository::class],
            $app['place_iri_generator']
        );
    }
);

$app['duplicate_place_repository'] = $app->share(
    function ($app) {
        return new DBALDuplicatePlaceRepository(
            $app['dbal_connection']
        );
    }
);

$app['canonical_service'] = $app->share(
    function ($app) {
        return new CanonicalService(
            $app['config']['museumpas']['label'],
            $app['duplicate_place_repository'],
            $app[EventRelationsRepository::class],
            new DBALReadRepository(
                $app['dbal_connection'],
                new StringLiteral('labels_relations')
            ),
            $app['place_jsonld_repository']
        );
    }
);

/** Organizer **/

$app['organizer_iri_generator'] = $app->share(
    function ($app) {
        return new CallableIriGenerator(
            function ($cdbid) use ($app) {
                return $app['config']['url'] . '/organizers/' . $cdbid;
            }
        );
    }
);

$app->register(new OrganizerRequestHandlerServiceProvider());
$app->register(new OrganizerJSONLDServiceProvider());
$app->register(new OrganizerCommandHandlerProvider());

$app['eventstore_payload_serializer'] = $app->share(
    function ($app) {
        return \CultuurNet\UDB3\BackwardsCompatiblePayloadSerializerFactory::createSerializer(
            $app[LabelServiceProvider::JSON_READ_REPOSITORY]
        );
    }
);

$app['organizer_store'] = $app->share(
    function ($app) {
        $eventStore = $app['event_store_factory'](AggregateType::organizer());

        return new UniqueDBALEventStoreDecorator(
            $eventStore,
            $app['dbal_connection'],
            'organizer_unique_websites',
            new WebsiteUniqueConstraintService(new WebsiteNormalizer())
        );
    }
);

$app['organizers_locator_event_stream_decorator'] = $app->share(
    function (Application $app) {
        return new OfferLocator($app['organizer_iri_generator']);
    }
);

$app['organizer_repository'] = $app->share(
    function (Application $app) {
        $repository = new \CultuurNet\UDB3\Organizer\OrganizerRepository(
            $app['organizer_store'],
            $app[EventBus::class],
            array(
                $app['event_stream_metadata_enricher'],
                $app['organizers_locator_event_stream_decorator']
            )
        );

        return $repository;
    }
);

$app['organizer_service'] = $app->share(
    function ($app) {
        $service = new \CultuurNet\UDB3\OrganizerService(
            $app['organizer_jsonld_repository'],
            $app['organizer_repository'],
            $app['organizer_iri_generator']
        );

        return $service;
    }
);

/** Roles */

$app['role_iri_generator'] = $app->share(
    function ($app) {
        return new CallableIriGenerator(
            function ($roleId) use ($app) {
                return $app['config']['url'] . '/roles/' . $roleId;
            }
        );
    }
);

$app['role_store'] = $app->share(
    function ($app) {
        return $app['event_store_factory'](AggregateType::role());
    }
);

$app['real_role_repository'] = $app->share(
    function ($app) {
        $repository = new \CultuurNet\UDB3\Role\RoleRepository(
            $app['role_store'],
            $app[EventBus::class]
        );

        return $repository;
    }
);

// There is a role_read_repository that broadcasts any changes to role details.
// Use the repository to make changes, else other read models that contain role details will not be updated.
$app['role_detail_cache'] = $app->share(
    function ($app) {
        return $app['cache']('role_detail');
    }
);

$app['user_roles_cache'] = $app->share(
    function ($app) {
        return $app['cache']('user_roles');
    }
);

$app['role_read_repository'] = $app->share(
    function ($app) {
        return new \CultuurNet\UDB3\ReadModel\BroadcastingDocumentRepositoryDecorator(
            new \CultuurNet\UDB3\Doctrine\ReadModel\CacheDocumentRepository(
                $app['role_detail_cache']
            ),
            $app[EventBus::class],
            new \CultuurNet\UDB3\Role\ReadModel\Detail\EventFactory()
        );
    }
);

$app['user_roles_repository'] = $app->share(
    function ($app) {
        return new \CultuurNet\UDB3\Doctrine\ReadModel\CacheDocumentRepository(
            $app['user_roles_cache']
        );
    }
);

$app['role_search_v3_repository.table_name'] = new StringLiteral('roles_search_v3');

$app['role_search_v3_repository'] = $app->share(
    function ($app) {
        return new \CultuurNet\UDB3\Role\ReadModel\Search\Doctrine\DBALRepository(
            $app['dbal_connection'],
            $app['role_search_v3_repository.table_name']
        );
    }
);

$app['role_search_v3_projector'] = $app->share(
    function ($app) {
        return new \CultuurNet\UDB3\Role\ReadModel\Search\Projector(
            $app['role_search_v3_repository'],
        );
    }
);

$app['role_detail_projector'] = $app->share(
    function ($app) {
        return new \CultuurNet\UDB3\Role\ReadModel\Detail\Projector(
            $app['role_read_repository']
        );
    }
);

$app['user_roles_projector'] = $app->share(
    function ($app) {
        return new \CultuurNet\UDB3\Role\ReadModel\Users\UserRolesProjector(
            $app['user_roles_repository'],
            $app['role_read_repository'],
            $app['role_users_read_repository']
        );
    }
);

$app['role_permissions_cache'] = $app->share(
    function ($app) {
        return $app['cache']('role_permissions');
    }
);

$app['role_labels_cache'] = $app->share(
    function ($app) {
        return $app['cache']('role_labels');
    }
);

$app['role_labels_read_repository'] = $app->share(
    function ($app) {
        return new \CultuurNet\UDB3\Doctrine\ReadModel\CacheDocumentRepository(
            $app['role_labels_cache']
        );
    }
);

$app['role_labels_projector'] = $app->share(
    function ($app) {
        return new \CultuurNet\UDB3\Role\ReadModel\Labels\RoleLabelsProjector(
            $app['role_labels_read_repository'],
            $app['labels.json_read_repository'],
            $app['label_roles_read_repository']
        );
    }
);

$app['label_roles_cache'] = $app->share(
    function ($app) {
        return $app['cache']('label_roles');
    }
);

$app['label_roles_read_repository'] = $app->share(
    function ($app) {
        return new \CultuurNet\UDB3\Doctrine\ReadModel\CacheDocumentRepository(
            $app['label_roles_cache']
        );
    }
);

$app['label_roles_projector'] = $app->share(
    function ($app) {
        return new \CultuurNet\UDB3\Role\ReadModel\Labels\LabelRolesProjector(
            $app['label_roles_read_repository']
        );
    }
);

$app['role_users_cache'] = $app->share(
    function ($app) {
        return $app['cache']('role_users');
    }
);

$app['role_users_read_repository'] = $app->share(
    function ($app) {
        return new \CultuurNet\UDB3\Doctrine\ReadModel\CacheDocumentRepository(
            $app['role_users_cache']
        );
    }
);

$app['role_users_projector'] = $app->share(
    function ($app) {
        return new \CultuurNet\UDB3\Role\ReadModel\Users\RoleUsersProjector(
            $app['role_users_read_repository'],
            $app[Auth0UserIdentityResolver::class]
        );
    }
);

$app['event_export_notification_mail_factory'] = $app->share(
    function ($app) {
        return new \CultuurNet\UDB3\EventExport\Notification\Swift\DefaultMessageFactory(
            new \CultuurNet\UDB3\EventExport\Notification\DefaultPlainTextBodyFactory(),
            new \CultuurNet\UDB3\EventExport\Notification\DefaultHTMLBodyFactory(),
            new \CultuurNet\UDB3\EventExport\Notification\LiteralSubjectFactory(
                $app['config']['export']['mail']['subject']
            ),
            $app['config']['mail']['sender']['address'],
            $app['config']['mail']['sender']['name']
        );
    }
);

$app['uitpas'] = $app->share(
    function (Application $app) {
        /** @var CultureFeed $cultureFeed */
        $cultureFeed = $app['culturefeed'];
        return $cultureFeed->uitpas();
    }
);

// This service is used by the background worker to impersonate the user
// who initially queued the command.
$app['impersonator'] = $app->share(
    function () {
        return new \CultuurNet\UDB3\Impersonator();
    }
);

$app->register(
    new AMQPConnectionServiceProvider(),
    [
        'amqp.connection.host' => $app['config']['amqp']['host'],
        'amqp.connection.port' => $app['config']['amqp']['port'],
        'amqp.connection.user' => $app['config']['amqp']['user'],
        'amqp.connection.password' => $app['config']['amqp']['password'],
        'amqp.connection.vhost' => $app['config']['amqp']['vhost'],
    ]
);

$app->register(
    new AMQPPublisherServiceProvider(),
    [
        'amqp.publisher.exchange_name' => $app['config']['amqp']['publish']['udb3']['exchange'],
        'amqp.publisher.cli.client_ids' => $app['config']['amqp']['publish']['udb3']['cli']['client_ids'],
        'amqp.publisher.cli.api_keys' => $app['config']['amqp']['publish']['udb3']['cli']['api_keys'],
    ]
);

$app->register(new MetadataServiceProvider());

$app->register(new \CultuurNet\UDB3\Silex\Export\ExportServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\Event\EventEditingServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\Event\EventReadServiceProvider());
$app->register(new EventCommandHandlerProvider());
$app->register(new EventRequestHandlerServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\Place\PlaceEditingServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\Place\PlaceReadServiceProvider());
$app->register(new PlaceRequestHandlerServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\User\UserServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\Event\EventPermissionServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\Place\PlacePermissionServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\Organizer\OrganizerPermissionServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\Offer\OfferServiceProvider());
$app->register(new LabelServiceProvider());
$app->register(new RoleRequestHandlerServiceProvider());
$app->register(new UserPermissionsServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\Event\ProductionServiceProvider());
$app->register(new UiTPASServiceLabelsServiceProvider());
$app->register(new UiTPASServiceEventServiceProvider());
$app->register(new UiTPASServiceOrganizerServiceProvider());

$app->register(
    new \CultuurNet\UDB3\Silex\Media\MediaServiceProvider(),
    [
        'media.upload_directory' => $app['config']['media']['upload_directory'],
        'media.media_directory' => $app['config']['media']['media_directory'],
        'media.file_size_limit' => $app['config']['media']['file_size_limit'] ?? 1000000
     ],
);

$app->register(new ImageStorageProvider());

$app['predis.client'] = $app->share(function ($app) {
    $redisURI = isset($app['config']['redis']['uri']) ?
        $app['config']['redis']['uri'] : 'tcp://127.0.0.1:6379';

    return new Predis\Client($redisURI);
});

$app->register(new Sapi3SearchServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\Offer\BulkLabelOfferServiceProvider());

// Provides authentication of HTTP requests. While the HTTP authentication is not needed in CLI context, the service
// provider still needs to be registered in the general bootstrap.php instead of web/index.php so CLI commands have
// access to services like CurrentUser, which is also provided when an async job is being handled in the CLI and the
// user who triggered the job is being impersonated.
$app->register(new AuthServiceProvider());

$app->register(
    new \CultuurNet\UDB3\Silex\UDB2IncomingEventServicesProvider(),
    [
        'udb2_place_external_id_mapping.yml_file_location' => $udb3ConfigLocation . '/external_id_mapping_place.yml',
        'udb2_organizer_external_id_mapping.yml_file_location' => $udb3ConfigLocation . '/external_id_mapping_organizer.yml',
        'udb2_cdbxml_enricher.http_response_timeout' => isset($app['config']['udb2_cdbxml_enricher']['http_response_timeout']) ? $app['config']['udb2_cdbxml_enricher']['http_response_timeout'] : 3,
        'udb2_cdbxml_enricher.http_connect_timeout' => isset($app['config']['udb2_cdbxml_enricher']['http_connect_timeout']) ? $app['config']['udb2_cdbxml_enricher']['http_connect_timeout'] : 1,
        'udb2_cdbxml_enricher.xsd' => $app['config']['udb2_cdbxml_enricher']['xsd'],
        'udb2_cdbxml_enricher.media_uuid_regex' => $app['config']['udb2_cdbxml_enricher']['media_uuid_regex'],
    ]
);

$app->register(new \CultuurNet\UDB3\Silex\UiTPAS\UiTPASIncomingEventServicesProvider());

$app->register(
    new \CultuurNet\UDB3\Silex\GeocodingServiceProvider(),
    [
        'geocoding_service.google_maps_api_key' => isset($app['config']['google_maps_api_key']) ? $app['config']['google_maps_api_key'] : null,
    ]
);

$app->register(new \CultuurNet\UDB3\Silex\Place\PlaceGeoCoordinatesServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\Event\EventGeoCoordinatesServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\Organizer\OrganizerGeoCoordinatesServiceProvider());

$app->register(new EventHistoryServiceProvider());
$app->register(new PlaceHistoryServiceProvider());

$app->register(new \CultuurNet\UDB3\Silex\Import\ImportConsumerServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\Media\MediaImportServiceProvider());

$app->register(new CuratorsServiceProvider());

$app->register(new Auth0ServiceProvider());

$app->register(new TermServiceProvider());

$app->register(new JobsServiceProvider());

if (isset($app['config']['bookable_event']['dummy_place_ids'])) {
    LocationId::setDummyPlaceForEducationIds($app['config']['bookable_event']['dummy_place_ids']);
}

return $app;

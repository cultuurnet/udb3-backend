<?php

use Broadway\EventHandling\EventBus;
use CultuurNet\UDB3\AggregateType;
use CultuurNet\UDB3\AMQP\AMQPConnectionServiceProvider;
use CultuurNet\UDB3\AMQP\AMQPPublisherServiceProvider;
use CultuurNet\UDB3\ApiName;
use CultuurNet\UDB3\Auth0\Auth0ServiceProvider;
use CultuurNet\UDB3\Authentication\AuthServiceProvider;
use CultuurNet\UDB3\CalendarFactory;
use CultuurNet\UDB3\Clock\SystemClock;
use CultuurNet\UDB3\CommandHandling\CommandBusServiceProvider;
use CultuurNet\UDB3\Culturefeed\CultureFeedServiceProvider;
use CultuurNet\UDB3\Curators\CuratorsServiceProvider;
use CultuurNet\UDB3\Error\LoggerFactory;
use CultuurNet\UDB3\Error\LoggerName;
use CultuurNet\UDB3\Error\SentryServiceProvider;
use CultuurNet\UDB3\Event\EventCommandHandlerProvider;
use CultuurNet\UDB3\Event\EventEditingServiceProvider;
use CultuurNet\UDB3\Event\EventGeoCoordinatesServiceProvider;
use CultuurNet\UDB3\Event\EventHistoryServiceProvider;
use CultuurNet\UDB3\Event\EventJSONLDServiceProvider;
use CultuurNet\UDB3\Event\EventPermissionServiceProvider;
use CultuurNet\UDB3\Event\EventReadServiceProvider;
use CultuurNet\UDB3\Event\EventRequestHandlerServiceProvider;
use CultuurNet\UDB3\Event\ProductionServiceProvider;
use CultuurNet\UDB3\Event\ReadModel\Relations\EventRelationsRepository;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\EventBus\EventBusServiceProvider;
use CultuurNet\UDB3\EventSourcing\DBAL\AggregateAwareDBALEventStore;
use CultuurNet\UDB3\EventSourcing\DBAL\UniqueDBALEventStoreDecorator;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Jobs\JobsServiceProvider;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\Doctrine\DBALReadRepository;
use CultuurNet\UDB3\Log\SocketIOEmitterHandler;
use CultuurNet\UDB3\Metadata\MetadataServiceProvider;
use CultuurNet\UDB3\Offer\OfferLocator;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\CdbXmlContactInfoImporter;
use CultuurNet\UDB3\Organizer\OrganizerCommandHandlerProvider;
use CultuurNet\UDB3\Organizer\OrganizerJSONLDServiceProvider;
use CultuurNet\UDB3\Organizer\OrganizerRequestHandlerServiceProvider;
use CultuurNet\UDB3\Organizer\WebsiteNormalizer;
use CultuurNet\UDB3\Organizer\WebsiteUniqueConstraintService;
use CultuurNet\UDB3\Place\Canonical\CanonicalService;
use CultuurNet\UDB3\Place\Canonical\DBALDuplicatePlaceRepository;
use CultuurNet\UDB3\Place\LocalPlaceService;
use CultuurNet\UDB3\Place\ReadModel\Relations\PlaceRelationsRepository;
use CultuurNet\UDB3\Security\GeneralSecurityServiceProvider;
use CultuurNet\UDB3\Security\OfferSecurityServiceProvider;
use CultuurNet\UDB3\Security\OrganizerSecurityServiceProvider;
use CultuurNet\UDB3\Silex\Container\HybridContainerApplication;
use CultuurNet\UDB3\Silex\Container\PimplePSRContainerBridge;
use CultuurNet\UDB3\Labels\LabelServiceProvider;
use CultuurNet\UDB3\Media\ImageStorageProvider;
use CultuurNet\UDB3\Place\PlaceHistoryServiceProvider;
use CultuurNet\UDB3\Place\PlaceJSONLDServiceProvider;
use CultuurNet\UDB3\Place\PlaceRequestHandlerServiceProvider;
use CultuurNet\UDB3\Silex\Role\RoleRequestHandlerServiceProvider;
use CultuurNet\UDB3\Silex\Role\UserPermissionsServiceProvider;
use CultuurNet\UDB3\Search\Sapi3SearchServiceProvider;
use CultuurNet\UDB3\UiTPASService\UiTPASServiceEventServiceProvider;
use CultuurNet\UDB3\UiTPASService\UiTPASServiceLabelsServiceProvider;
use CultuurNet\UDB3\UiTPASService\UiTPASServiceOrganizerServiceProvider;
use CultuurNet\UDB3\StringLiteral;
use CultuurNet\UDB3\Term\TermServiceProvider;
use CultuurNet\UDB3\User\Auth0UserIdentityResolver;
use League\Container\Container;
use League\Container\ReflectionContainer;
use Monolog\Logger;
use Silex\Application;
use SocketIO\Emitter;

date_default_timezone_set('Europe/Brussels');

/**
 * Disable warnings for calling new SimpleXmlElement() with invalid XML.
 * An exception will still be thrown, but no warnings will be generated (which are hard to catch/hide otherwise).
 * We do this system-wide because we parse XML in various places (UiTPAS API responses, UiTID v1 responses, imported UDB2 XML, ...)
 */
libxml_use_internal_errors(true);

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

$app['config'] = file_exists(__DIR__ . '/config.php') ? require __DIR__ . '/config.php' : [];

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

$container->addServiceProvider(new SentryServiceProvider());

$container->addServiceProvider(new \CultuurNet\UDB3\SavedSearches\SavedSearchesServiceProvider());

$container->addServiceProvider(new CommandBusServiceProvider());
$container->addServiceProvider(new EventBusServiceProvider());

/**
 * CultureFeed services.
 */
$container->addServiceProvider(new CultureFeedServiceProvider());

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

$container->addServiceProvider(new GeneralSecurityServiceProvider());
$container->addServiceProvider(new OfferSecurityServiceProvider());
$container->addServiceProvider(new OrganizerSecurityServiceProvider());

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

$container->addServiceProvider(new EventJSONLDServiceProvider());

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

/** Production */


/** Place **/

$container->addShared(
    'place_iri_generator',
    fn () => new CallableIriGenerator(
        fn ($cdbid) => $container->get('config')['url'] . '/place/' . $cdbid
    )
);

$container->addServiceProvider(new PlaceJSONLDServiceProvider());

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

// @todo: remove usages of 'place_repository' with Class based share
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
$container->addShared(
    \CultuurNet\UDB3\Place\PlaceRepository::class,
    function () use ($container): \CultuurNet\UDB3\Place\PlaceRepository {
        return $container->get('place_repository');
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

$container->addServiceProvider(new OrganizerRequestHandlerServiceProvider());
$container->addServiceProvider(new OrganizerJSONLDServiceProvider());
$container->addServiceProvider(new OrganizerCommandHandlerProvider());

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

/**
 * @todo move this to a class.
 */
const ROLE_SEARCH_V3_REPOSITORY_TABLE_NAME = 'roles_search_v3';

$app['role_search_v3_repository'] = $app->share(
    function ($app) {
        return new \CultuurNet\UDB3\Role\ReadModel\Search\Doctrine\DBALRepository(
            $app['dbal_connection'],
            new StringLiteral(ROLE_SEARCH_V3_REPOSITORY_TABLE_NAME)
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

$container->addServiceProvider(
    new AMQPConnectionServiceProvider()
);

$container->addServiceProvider(
    new AMQPPublisherServiceProvider()
);

$container->addServiceProvider(new MetadataServiceProvider());

$container->addServiceProvider(new \CultuurNet\UDB3\Export\ExportServiceProvider());
$container->addServiceProvider(new EventEditingServiceProvider());
$container->addServiceProvider(new EventReadServiceProvider());
$container->addServiceProvider(new EventCommandHandlerProvider());
$container->addServiceProvider(new EventRequestHandlerServiceProvider());
$container->addServiceProvider(new \CultuurNet\UDB3\Place\PlaceEditingServiceProvider());
$container->addServiceProvider(new \CultuurNet\UDB3\Place\PlaceReadServiceProvider());
$container->addServiceProvider(new PlaceRequestHandlerServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\User\UserServiceProvider());
$container->addServiceProvider(new EventPermissionServiceProvider());
$container->addServiceProvider(new \CultuurNet\UDB3\Place\PlacePermissionServiceProvider());
$container->addServiceProvider(new \CultuurNet\UDB3\Organizer\OrganizerPermissionServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\Offer\OfferServiceProvider());
$container->addServiceProvider(new LabelServiceProvider());
$app->register(new RoleRequestHandlerServiceProvider());
$app->register(new UserPermissionsServiceProvider());
$container->addServiceProvider(new ProductionServiceProvider());
$container->addServiceProvider(new UiTPASServiceLabelsServiceProvider());
$container->addServiceProvider(new UiTPASServiceEventServiceProvider());
$container->addServiceProvider(new UiTPASServiceOrganizerServiceProvider());

$container->addServiceProvider(
    new \CultuurNet\UDB3\Media\MediaServiceProvider()
);

$container->addServiceProvider(new ImageStorageProvider());

$app['predis.client'] = $app->share(function ($app) {
    $redisURI = isset($app['config']['redis']['uri']) ?
        $app['config']['redis']['uri'] : 'tcp://127.0.0.1:6379';

    return new Predis\Client($redisURI);
});

$container->addServiceProvider(new Sapi3SearchServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\Offer\BulkLabelOfferServiceProvider());

// Provides authentication of HTTP requests. While the HTTP authentication is not needed in CLI context, the service
// provider still needs to be registered in the general bootstrap.php instead of web/index.php so CLI commands have
// access to services like CurrentUser, which is also provided when an async job is being handled in the CLI and the
// user who triggered the job is being impersonated.
$container->addServiceProvider(new AuthServiceProvider());

$app->register(
    new \CultuurNet\UDB3\Silex\UDB2EventServicesProvider(),
    [
        'udb2_place_external_id_mapping.file_location' => $udb3ConfigLocation . '/config.external_id_mapping_place.php',
        'udb2_organizer_external_id_mapping.file_location' => $udb3ConfigLocation . '/config.external_id_mapping_organizer.php',
    ]
);

$app->register(new \CultuurNet\UDB3\Silex\UiTPAS\UiTPASIncomingEventServicesProvider());

$app->register(
    new \CultuurNet\UDB3\Silex\GeocodingServiceProvider(),
    [
        'geocoding_service.google_maps_api_key' => isset($app['config']['google_maps_api_key']) ? $app['config']['google_maps_api_key'] : null,
    ]
);

$container->addServiceProvider(new \CultuurNet\UDB3\Place\PlaceGeoCoordinatesServiceProvider());
$container->addServiceProvider(new EventGeoCoordinatesServiceProvider());
$container->addServiceProvider(new \CultuurNet\UDB3\Organizer\OrganizerGeoCoordinatesServiceProvider());

$container->addServiceProvider(new EventHistoryServiceProvider());
$container->addServiceProvider(new PlaceHistoryServiceProvider());

$container->addServiceProvider(new \CultuurNet\UDB3\Media\MediaImportServiceProvider());

$container->addServiceProvider(new CuratorsServiceProvider());

$container->addServiceProvider(new Auth0ServiceProvider());

$container->addServiceProvider(new TermServiceProvider());

$container->addServiceProvider(new JobsServiceProvider());

if (isset($app['config']['bookable_event']['dummy_place_ids'])) {
    LocationId::setDummyPlaceForEducationIds($app['config']['bookable_event']['dummy_place_ids']);
}

return $container;

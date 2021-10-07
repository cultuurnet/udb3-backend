<?php

use Broadway\CommandHandling\CommandBus;
use Broadway\EventHandling\EventBus;
use CultuurNet\UDB3\Broadway\EventHandling\ReplayFlaggingEventBus;
use CultuurNet\UDB3\CalendarFactory;
use CultuurNet\UDB3\Clock\SystemClock;
use CultuurNet\UDB3\Event\CommandHandlers\UpdateSubEventsHandler;
use CultuurNet\UDB3\Event\EventOrganizerRelationService;
use CultuurNet\UDB3\Event\ExternalEventService;
use CultuurNet\UDB3\Event\LocationMarkedAsDuplicateProcessManager;
use CultuurNet\UDB3\Event\Productions\ProductionCommandHandler;
use CultuurNet\UDB3\Event\RelocateEventToCanonicalPlace;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\EventSourcing\DBAL\AggregateAwareDBALEventStore;
use CultuurNet\UDB3\EventSourcing\DBAL\UniqueDBALEventStoreDecorator;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Jwt\Symfony\Authentication\JsonWebToken;
use CultuurNet\UDB3\Log\SocketIOEmitterHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\AddLabelHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\AddVideoHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\ChangeOwnerHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\ImportLabelsHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\RemoveLabelHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\UpdateBookingAvailabilityHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\UpdateCalendarHandler;
use CultuurNet\UDB3\Offer\CommandHandlers\UpdateStatusHandler;
use CultuurNet\UDB3\Offer\OfferLocator;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\CdbXmlContactInfoImporter;
use CultuurNet\UDB3\Offer\ReadModel\Metadata\OfferMetadataProjector;
use CultuurNet\UDB3\Organizer\WebsiteNormalizer;
use CultuurNet\UDB3\Organizer\WebsiteUniqueConstraintService;
use CultuurNet\UDB3\Place\LocalPlaceService;
use CultuurNet\UDB3\Place\MarkAsDuplicateCommandHandler;
use CultuurNet\UDB3\Place\PlaceOrganizerRelationService;
use CultuurNet\UDB3\Silex\AggregateType;
use CultuurNet\UDB3\Silex\AMQP\AMQPConnectionServiceProvider;
use CultuurNet\UDB3\Silex\AMQP\AMQPPublisherServiceProvider;
use CultuurNet\UDB3\Silex\ApiName;
use CultuurNet\UDB3\Silex\Auth0\Auth0ServiceProvider;
use CultuurNet\UDB3\Silex\CommandHandling\LazyLoadingCommandBus;
use CultuurNet\UDB3\Silex\CultureFeed\CultureFeedServiceProvider;
use CultuurNet\UDB3\Silex\Curators\CuratorsServiceProvider;
use CultuurNet\UDB3\Silex\Error\LoggerFactory;
use CultuurNet\UDB3\Silex\Error\LoggerName;
use CultuurNet\UDB3\Silex\Error\SentryServiceProvider;
use CultuurNet\UDB3\Silex\Event\EventHistoryServiceProvider;
use CultuurNet\UDB3\Silex\Event\EventJSONLDServiceProvider;
use CultuurNet\UDB3\Silex\Impersonator;
use CultuurNet\UDB3\Silex\Labels\LabelServiceProvider;
use CultuurNet\UDB3\Silex\Media\ImageStorageProvider;
use CultuurNet\UDB3\Silex\Metadata\MetadataServiceProvider;
use CultuurNet\UDB3\Silex\Organizer\OrganizerJSONLDServiceProvider;
use CultuurNet\UDB3\Silex\Organizer\OrganizerPermissionServiceProvider;
use CultuurNet\UDB3\Silex\Organizer\OrganizerServiceProvider;
use CultuurNet\UDB3\Silex\Place\PlaceHistoryServiceProvider;
use CultuurNet\UDB3\Silex\Place\PlaceJSONLDServiceProvider;
use CultuurNet\UDB3\Silex\Role\UserPermissionsServiceProvider;
use CultuurNet\UDB3\Silex\Search\Sapi3SearchServiceProvider;
use CultuurNet\UDB3\Silex\Security\GeneralSecurityServiceProvider;
use CultuurNet\UDB3\Silex\Security\OrganizerSecurityServiceProvider;
use CultuurNet\UDB3\Silex\Term\TermServiceProvider;
use CultuurNet\UDB3\Silex\Yaml\YamlConfigServiceProvider;
use CultuurNet\UDB3\User\Auth0UserIdentityResolver;
use Http\Adapter\Guzzle6\Client;
use JDesrosiers\Silex\Provider\CorsServiceProvider;
use Monolog\Logger;
use Silex\Application;
use Silex\Provider\Psr7ServiceProvider;
use SocketIO\Emitter;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use ValueObjects\StringLiteral\StringLiteral;

date_default_timezone_set('Europe/Brussels');

$app = new Application();

$app['api_name'] = ApiName::UNKNOWN;

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

$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

$app->register(new \CultuurNet\UDB3\Silex\SavedSearches\SavedSearchesServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\Http\HttpServiceProvider());

$app->register(new Silex\Provider\ServiceControllerServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\CommandHandling\CommandBusServiceProvider());

$app->register(new CorsServiceProvider());

$app['local_domain'] = \ValueObjects\Web\Domain::specifyType(
    parse_url($app['config']['url'])['host']
);

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

$app['event_service'] = $app->share(
    function ($app) {
        $service = new \CultuurNet\UDB3\Event\LocalEventService(
            $app['event_jsonld_repository'],
            $app['event_repository'],
            $app['event_relations_repository'],
            $app['event_iri_generator']
        );

        return $service;
    }
);

$app['external_event_service'] = $app->share(
    function ($app) {
        return new ExternalEventService($app['http.guzzle']);
    }
);

$app['current_user_id'] = $app::share(
    function (Application $app) {
        /* @var Impersonator $impersonator */
        $impersonator = $app['impersonator'];
        if ($impersonator->getUserId()) {
            return $impersonator->getUserId();
        }

        try {
            /* @var TokenStorageInterface $tokenStorage */
            $tokenStorage = $app['security.token_storage'];
        } catch (\InvalidArgumentException $e) {
            // Running from CLI or unauthorized (will be further handled by the firewall).
            return null;
        }

        $token = $tokenStorage->getToken();
        if (!($token instanceof JsonWebToken)) {
            // The token in the firewall storage is not supported.
            return null;
        }
        return $token->getUserId();
    }
);

$app['current_user_is_god_user'] = $app::share(
    function (Application $app) {
        return in_array(
            $app['current_user_id'],
            $app['config']['user_permissions']['allow_all'],
            true
        );
    }
);

$app['jwt'] = $app::share(
    function (Application $app) {
        // Check first if we're impersonating someone.
        /* @var Impersonator $impersonator */
        $impersonator = $app['impersonator'];
        if ($impersonator->getJwt()) {
            return $impersonator->getJwt();
        }

        try {
            /* @var TokenStorageInterface $tokenStorage */
            $tokenStorage = $app['security.token_storage'];
        } catch (\InvalidArgumentException $e) {
            // Running from CLI.
            return null;
        }

        $token = $tokenStorage->getToken();

        if ($token instanceof JsonWebToken) {
            return $token;
        }

        return null;
    }
);

$app['api_key'] = $app->share(
    function (Application $app) {
        // Check first if we're impersonating someone.
        // This is done when handling commands.
        /* @var Impersonator $impersonator */
        $impersonator = $app['impersonator'];
        if ($impersonator->getApiKey()) {
            return $impersonator->getApiKey();
        }

        // If not impersonating then use the api key from the request.
        // It is possible to work without api key then null is returned
        // and will be handled with a pass through authorizer.
        return isset($app['auth.api_key']) ? $app['auth.api_key'] : null;
    }
);

$app['api_client_id'] = $app::share(
    function (Application $app) {
        $token = $app['jwt'];
        if ($token instanceof JsonWebToken) {
            return $token->getClientId();
        }
        return null;
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
        return $app['event_store_factory'](AggregateType::EVENT());
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

$app['calendar_summary_repository'] = $app->share(
    function ($app) {
        // At the moment the calendar-summary is accessible through this app via proxy.
        if (isset($app['config']['url'])) {
            return new \CultuurNet\UDB3\EventExport\CalendarSummary\HttpCalendarSummaryRepository(
                new Client(new \GuzzleHttp\Client()),
                \League\Uri\Http::createFromString($app['config']['url'])
            );
        } else {
            return null;
        }
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

$app['event_relations_projector'] = $app->share(
    function ($app) {
        return new \CultuurNet\UDB3\Event\ReadModel\Relations\Projector(
            $app['event_relations_repository'],
            $app['udb2_event_cdbid_extractor']
        );
    }
);

$app['place_relations_projector'] = $app->share(
    function ($app) {
        return new \CultuurNet\UDB3\Place\ReadModel\Relations\Projector(
            $app['place_relations_repository']
        );
    }
);

$app['event_bus'] = function ($app) {
    $eventBus = new \CultuurNet\UDB3\SimpleEventBus();

    $eventBus->beforeFirstPublication(function (EventBus $eventBus) use ($app) {
        $subscribers = [
            'event_relations_projector',
            'place_relations_projector',
            EventJSONLDServiceProvider::PROJECTOR,
            EventJSONLDServiceProvider::RELATED_PROJECTOR,
            \CultuurNet\UDB3\Event\ReadModel\History\HistoryProjector::class,
            \CultuurNet\UDB3\Place\ReadModel\History\HistoryProjector::class,
            PlaceJSONLDServiceProvider::PROJECTOR,
            PlaceJSONLDServiceProvider::RELATED_PROJECTOR,
            OrganizerJSONLDServiceProvider::PROJECTOR,
            'event_calendar_projector',
            'event_permission.projector',
            'place_permission.projector',
            OrganizerPermissionServiceProvider::PERMISSION_PROJECTOR,
            'amqp.publisher',
            'udb2_events_cdbxml_enricher',
            'udb2_actor_events_cdbxml_enricher',
            'udb2_events_to_udb3_event_applier',
            'udb2_actor_events_to_udb3_place_applier',
            'udb2_actor_events_to_udb3_organizer_applier',
            'udb2_label_importer',
            LabelServiceProvider::JSON_PROJECTOR,
            LabelServiceProvider::RELATIONS_PROJECTOR,
            LabelServiceProvider::EVENT_LABEL_PROJECTOR,
            LabelServiceProvider::PLACE_LABEL_PROJECTOR,
            LabelServiceProvider::ORGANIZER_LABEL_PROJECTOR,
            LabelServiceProvider::LABEL_ROLES_PROJECTOR,
            'role_detail_projector',
            'role_labels_projector',
            'label_roles_projector',
            'role_search_v3_projector',
            'role_users_projector',
            'user_roles_projector',
            UserPermissionsServiceProvider::USER_PERMISSIONS_PROJECTOR,
            OfferMetadataProjector::class,
            'place_geocoordinates_process_manager',
            'event_geocoordinates_process_manager',
            'organizer_geocoordinates_process_manager',
            'uitpas_event_process_manager',
            'curators_news_article_process_manager',
            LocationMarkedAsDuplicateProcessManager::class,
            RelocateEventToCanonicalPlace::class,
        ];

        $initialSubscribersCount = count($subscribers);
        $subscribers = array_unique($subscribers);
        if ($initialSubscribersCount != count($subscribers)) {
            throw new \Exception('Some projectors are subscribed more then once!');
        }

        // Allow to override event bus subscribers through configuration.
        // The event replay command line utility uses this.
        if (
            isset($app['config']['event_bus']) &&
            isset($app['config']['event_bus']['subscribers'])
        ) {

            $subscribers = $app['config']['event_bus']['subscribers'];
        }

        if (
            isset($app['config']['event_bus']) &&
            isset($app['config']['event_bus']['disable_related_offer_subscribers']) &&
            $app['config']['event_bus']['disable_related_offer_subscribers'] == true
        ) {
            $subscribersToDisable = [
                EventJSONLDServiceProvider::RELATED_PROJECTOR,
                PlaceJSONLDServiceProvider::RELATED_PROJECTOR,
            ];
            $subscribers = array_diff($subscribers, $subscribersToDisable);
        }

        foreach ($subscribers as $subscriberServiceId) {
            $eventBus->subscribe($app[$subscriberServiceId]);
        }
    });

    return $eventBus;
};

$app->extend(
    'event_bus',
    function (EventBus $eventBus) {
        return new ReplayFlaggingEventBus($eventBus);
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
            $app['event_bus'],
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

        return LoggerFactory::create($app, LoggerName::forResqueWorker($queueName), [$socketIOHandler]);
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

        $commandBus->subscribe(new UpdateSubEventsHandler($app['event_repository']));

        $commandBus->subscribe($app['saved_searches_command_handler']);

        $commandBus->subscribe(
            new \CultuurNet\UDB3\Place\CommandHandler(
                $app['place_repository'],
                $app['organizer_repository'],
                $app['media_manager']
            )
        );

        $commandBus->subscribe(new MarkAsDuplicateCommandHandler($app['place_repository']));

        $commandBus->subscribe(
            (new \CultuurNet\UDB3\Organizer\OrganizerCommandHandler($app['organizer_repository']))
                ->withOrganizerRelationService($app[PlaceOrganizerRelationService::class])
                ->withOrganizerRelationService($app[EventOrganizerRelationService::class])
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
        $commandBus->subscribe($app[UpdateCalendarHandler::class]);
        $commandBus->subscribe($app[UpdateStatusHandler::class]);
        $commandBus->subscribe($app[UpdateBookingAvailabilityHandler::class]);
        $commandBus->subscribe($app[ChangeOwnerHandler::class]);
        $commandBus->subscribe($app[AddLabelHandler::class]);
        $commandBus->subscribe($app[RemoveLabelHandler::class]);
        $commandBus->subscribe($app[ImportLabelsHandler::class]);
        $commandBus->subscribe($app[AddVideoHandler::class]);

        // Organizer command handlers
        $commandBus->subscribe($app[\CultuurNet\UDB3\Organizer\CommandHandler\AddLabelHandler::class]);
        $commandBus->subscribe($app[\CultuurNet\UDB3\Organizer\CommandHandler\RemoveLabelHandler::class]);
        $commandBus->subscribe($app[\CultuurNet\UDB3\Organizer\CommandHandler\ImportLabelsHandler::class]);

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

$app['event_relations_repository'] = $app->share(
    function ($app) {
        return new \CultuurNet\UDB3\Event\ReadModel\Relations\Doctrine\DBALRepository(
            $app['dbal_connection']
        );
    }
);

$app['place_relations_repository'] = $app->share(
    function ($app) {
        return new \CultuurNet\UDB3\Place\ReadModel\Relations\Doctrine\DBALRepository(
            $app['dbal_connection']
        );
    }
);

$app['place_store'] = $app->share(
    function ($app) {
        return $app['event_store_factory'](AggregateType::PLACE());
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
            $app['event_bus'],
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
            $app['place_relations_repository'],
            $app['place_iri_generator']
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

$app['organizer_editing_service'] = $app->share(
    function ($app) {
        return new \CultuurNet\UDB3\Organizer\DefaultOrganizerEditingService(
            $app['event_command_bus'],
            $app['uuid_generator'],
            $app['organizer_repository']
        );
    }
);

$app->register(new OrganizerServiceProvider());
$app->register(new OrganizerJSONLDServiceProvider());

$app['eventstore_payload_serializer'] = $app->share(
    function ($app) {
        return \CultuurNet\UDB3\BackwardsCompatiblePayloadSerializerFactory::createSerializer(
            $app[LabelServiceProvider::JSON_READ_REPOSITORY]
        );
    }
);

$app['organizer_store'] = $app->share(
    function ($app) {
        $eventStore = $app['event_store_factory'](AggregateType::ORGANIZER());

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
            $app['event_bus'],
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
        return $app['event_store_factory'](AggregateType::ROLE());
    }
);

$app['real_role_repository'] = $app->share(
    function ($app) {
        $repository = new \CultuurNet\UDB3\Role\RoleRepository(
            $app['role_store'],
            $app['event_bus']
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
            $app['event_bus'],
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

$app['role_service'] = $app->share(
    function ($app) {
        $service = new \CultuurNet\UDB3\LocalEntityService(
            $app['role_read_repository'],
            $app['real_role_repository'],
            $app['role_iri_generator']
        );

        return $service;
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
        return new \CultuurNet\UDB3\Silex\Impersonator();
    }
);

$app['amqp.content_type_map'] = $app->share(
    function () {
        return \CultuurNet\UDB3\Event\Events\ContentTypes::map() +
            \CultuurNet\UDB3\Place\Events\ContentTypes::map() +
            \CultuurNet\UDB3\Label\Events\ContentTypes::map() +
            \CultuurNet\UDB3\Organizer\Events\ContentTypes::map();
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
        'amqp.publisher.content_type_map' => $app['amqp.content_type_map'],
        'amqp.publisher.exchange_name' => $app['config']['amqp']['publish']['udb3']['exchange'],
    ]
);

$app->register(new MetadataServiceProvider());

$app->register(new \CultuurNet\UDB3\Silex\Proxy\ProxyServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\Export\ExportServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\Event\EventEditingServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\Event\EventReadServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\Place\PlaceEditingServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\Place\PlaceReadServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\User\UserServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\Event\EventPermissionServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\Place\PlacePermissionServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\Organizer\OrganizerPermissionServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\Offer\OfferServiceProvider());
$app->register(new LabelServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\Role\RoleEditingServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\Role\RoleReadingServiceProvider());
$app->register(new UserPermissionsServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\Event\ProductionServiceProvider());

$app->register(
    new \CultuurNet\UDB3\Silex\Media\MediaServiceProvider(),
    array(
        'media.upload_directory' => $app['config']['media']['upload_directory'],
        'media.media_directory' => $app['config']['media']['media_directory'],
        'media.file_size_limit' => new \ValueObjects\Number\Natural(
            isset($app['config']['media']['file_size_limit']) ?
                $app['config']['media']['file_size_limit'] :
                1000000
        ),
    )
);

$app->register(new ImageStorageProvider());

$app['predis.client'] = $app->share(function ($app) {
    $redisURI = isset($app['config']['redis']['uri']) ?
        $app['config']['redis']['uri'] : 'tcp://127.0.0.1:6379';

    return new Predis\Client($redisURI);
});

$app->register(new Sapi3SearchServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\Offer\BulkLabelOfferServiceProvider());

$app->register(
    new \CultuurNet\UDB3\Silex\Authentication\UitidApiKeyServiceProvider(),
    [
        'auth.api_key.group_id' => $app['config']['api_key']['group_id'],
        'auth.api_key_bypass' => $app['config']['bypass_api_key_check'] ?? false,
    ]
);

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

$app->register(new \CultuurNet\UDB3\Silex\UiTPAS\UiTPASCommandValidatorServiceProvider());
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

$app->register(new \CultuurNet\UDB3\Silex\Event\EventImportServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\Place\PlaceImportServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\Organizer\OrganizerImportServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\Import\ImportServiceProvider($subscribeCoreCommandHandlers));
$app->register(new \CultuurNet\UDB3\Silex\Import\ImportConsumerServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\Media\MediaImportServiceProvider());

$app->register(new CuratorsServiceProvider());

$app->register(new Auth0ServiceProvider());
$app->register(new Psr7ServiceProvider());

$app->register(new TermServiceProvider());

if (isset($app['config']['bookable_event']['dummy_place_ids'])) {
    LocationId::setDummyPlaceForEducationIds($app['config']['bookable_event']['dummy_place_ids']);
}

return $app;

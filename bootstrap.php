<?php

use Broadway\CommandHandling\CommandBusInterface;
use CommerceGuys\Intl\Currency\CurrencyRepository;
use CommerceGuys\Intl\NumberFormat\NumberFormatRepository;
use CultuurNet\SilexServiceProviderOAuth\OAuthServiceProvider;
use CultuurNet\SymfonySecurityJwt\Authentication\JwtUserToken;
use CultuurNet\SymfonySecurityOAuth\Model\Provider\TokenProviderInterface;
use CultuurNet\SymfonySecurityOAuth\Security\OAuthToken;
use CultuurNet\SymfonySecurityOAuthRedis\NonceProvider;
use CultuurNet\SymfonySecurityOAuthRedis\TokenProviderCache;
use CultuurNet\UDB3\Cdb\PriceDescriptionParser;
use CultuurNet\UDB3\Event\ExternalEventService;
use CultuurNet\UDB3\Event\ReadModel\JSONLD\CdbXMLImporter as EventCdbXMLImporter;
use CultuurNet\UDB3\EventSourcing\DBAL\UniqueDBALEventStoreDecorator;
use CultuurNet\UDB3\EventSourcing\ExecutionContextMetadataEnricher;
use CultuurNet\UDB3\Offer\OfferLocator;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\CdbXMLItemBaseImporter;
use CultuurNet\UDB3\Organizer\Events\WebsiteUniqueConstraintService;
use CultuurNet\UDB3\Place\ReadModel\JSONLD\CdbXMLImporter as PlaceCdbXMLImporter;
use CultuurNet\UDB3\ReadModel\Index\EntityIriGeneratorFactory;
use CultuurNet\UDB3\Silex\CultureFeed\CultureFeedServiceProvider;
use CultuurNet\UDB3\Silex\Impersonator;
use CultuurNet\UDB3\Silex\Labels\LabelServiceProvider;
use CultuurNet\UDB3\Silex\Role\UserPermissionsServiceProvider;
use CultuurNet\UDB3\UDB2\Media\Media as Udb2Media;
use Silex\Application;
use DerAlex\Silex\YamlConfigServiceProvider;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use JDesrosiers\Silex\Provider\CorsServiceProvider;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use ValueObjects\Number\Natural;
use ValueObjects\String\String as StringLiteral;

date_default_timezone_set('Europe/Brussels');

$app = new Application();

$adapter = new \League\Flysystem\Adapter\Local(__DIR__);
$app['local_file_system'] = new \League\Flysystem\Filesystem($adapter);

$app['debug'] = true;

if (!isset($udb3ConfigLocation)) {
    $udb3ConfigLocation =  __DIR__;
}
$app->register(new YamlConfigServiceProvider($udb3ConfigLocation . '/config.yml'));

$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

$app->register(new \CultuurNet\UDB3\Silex\SavedSearches\SavedSearchesServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\Variations\VariationsServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\Http\HttpServiceProvider());

$app->register(new Silex\Provider\ServiceControllerServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\Resque\ResqueCommandBusServiceProvider());

$app->register(new CorsServiceProvider(), array(
    "cors.allowOrigin" => implode(" ", $app['config']['cors']['origins']),
    "cors.allowCredentials" => true
));

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
        $timezoneName = empty($app['config']['timezone']) ? 'Europe/Brussels': $app['config']['timezone'];

        return new DateTimeZone($timezoneName);
    }
);

$app['clock'] = $app->share(
    function (Application $app) {
        return new \CultuurNet\Clock\SystemClock(
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

$app['entity_iri_generator_factory'] = $app->share(
    function ($app) {
        return new EntityIriGeneratorFactory($app['config']['url']);
    }
);

$app['uitid_consumer_credentials'] = $app->share(
    function ($app) {
        $consumerConfig = $app['config']['uitid']['consumer'];
        return new \CultuurNet\Auth\ConsumerCredentials(
            $consumerConfig['key'],
            $consumerConfig['secret']
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

$app['personal_variation_decorated_event_service'] = $app->share(
    function (Application $app) {
        $decoratedService = $app['external_event_service'];

        /* @var \CultureFeed_User $user */
        $user = $app['current_user'];

        $criteria = (new \CultuurNet\UDB3\Variations\ReadModel\Search\Criteria())
            ->withPurpose(
                new \CultuurNet\UDB3\Variations\Model\Properties\Purpose('personal')
            )
            ->withOwnerId(
                new \CultuurNet\UDB3\Variations\Model\Properties\OwnerId(
                    $user->id
                )
            );

        return new \CultuurNet\UDB3\Variations\VariationDecoratedEventService(
            $decoratedService,
            $app['variations.search'],
            $criteria,
            $app['variations.jsonld_repository'],
            $app['event_iri_generator']
        );
    }
);

$app['current_user'] = $app->share(
    function (Application $app) {
        // Check first if we're impersonating someone.
        /* @var Impersonator $impersonator */
        $impersonator = $app['impersonator'];
        if ($impersonator->getUser()) {
            return $impersonator->getUser();
        }

        try {
            /* @var TokenStorageInterface $tokenStorage */
            $tokenStorage = $app['security.token_storage'];
        } catch (\InvalidArgumentException $e) {
            // Running from CLI.
            return null;
        }

        $token = $tokenStorage->getToken();

        $cfUser = new \CultureFeed_User();

        if ($token instanceof JwtUserToken) {
            $jwt = $token->getCredentials();

            $cfUser->id = $jwt->getClaim('uid');
            $cfUser->nick = $jwt->getClaim('nick');
            $cfUser->mbox = $jwt->getClaim('email');

            return $cfUser;
        } else if ($token instanceof OAuthToken) {
            $tokenUser = $token->getUser();

            if ($tokenUser instanceof \CultuurNet\SymfonySecurityOAuthUitid\User) {
                $cfUser->id = $tokenUser->getUid();
                $cfUser->nick = $tokenUser->getUsername();
                $cfUser->mbox = $tokenUser->getEmail();
            }

            return $cfUser;
        } else {
            return null;
        }
    }
);

$app['jwt'] = $app->share(
    function(Application $app) {
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

        if ($token instanceof JwtUserToken) {
            return $token->getCredentials();
        }

        return null;
    }
);

$app['auth_service'] = $app->share(
    function ($app) {
        $uitidConfig = $app['config']['uitid'];

        return new CultuurNet\Auth\Guzzle\Service(
            $uitidConfig['base_url'],
            $app['uitid_consumer_credentials']
        );
    }
);

$app->register(new \CultuurNet\UDB3\Silex\Security\OfferSecurityServiceProvider());

$app['cache-redis'] = $app->share(
    function (Application $app) {
        $parameters = $app['config']['cache']['redis'];

        return function ($cacheType) use ($parameters) {
            $redisClient = new Predis\Client(
                $parameters,
                [
                    'prefix' => $cacheType . '_',
                ]
            );
            $cache = new Doctrine\Common\Cache\PredisCache($redisClient);

            return $cache;
        };
    }
);

$app['cache-filesystem'] = $app->share(
    function ($app) {
        $baseCacheDirectory = __DIR__ . '/cache';

        return function ($cacheType) use ($baseCacheDirectory) {
            $cacheDirectory = $baseCacheDirectory . '/' . $cacheType;

            $cache = new \Doctrine\Common\Cache\FilesystemCache($cacheDirectory);

            return $cache;
        };
    }
);

$app['cache'] = $app->share(
    function (Application $app) {
        $activeCacheType = $app['config']['cache']['active'] ?: 'filesystem';

        $cacheServiceName =  'cache-' . $activeCacheType;
        return $app[$cacheServiceName];
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

$app->register(new \CultuurNet\UDB3\Silex\PurgeServiceProvider());

$app['event_store'] = $app->share(
    function ($app) {
        return new \Broadway\EventStore\DBALEventStore(
            $app['dbal_connection'],
            $app['eventstore_payload_serializer'],
            new \Broadway\Serializer\SimpleInterfaceSerializer(),
            'events'
        );
    }
);

$app['event_jsonld_repository'] = $app->share(
    function ($app) {
        $cachedRepository =  new \CultuurNet\UDB3\Doctrine\Event\ReadModel\CacheDocumentRepository(
            $app['event_jsonld_cache']
        );

        $broadcastingRepository = new \CultuurNet\UDB3\ReadModel\BroadcastingDocumentRepositoryDecorator(
            $cachedRepository,
            $app['event_bus'],
            new \CultuurNet\UDB3\Event\ReadModel\JSONLD\EventFactory(
                $app['event_iri_generator']
            )
        );

        return $broadcastingRepository;
    }
);

$app['event_jsonld_cache'] = $app->share(
    function (Application $app) {
        return $app['cache']('event_jsonld');
    }
);

$app['udb2_media_iri_generator'] = $app->share(
    function (Application $app) {
        return new CallableIriGenerator(function (CultureFeed_Cdb_Data_File $file) use ($app) {
            $udb2Media = new Udb2Media($file);
            return $app['config']['url'] . '/media/' . $udb2Media->identify();
        });
    }
);

$app['event_cdbxml_importer'] = $app->share(
    function (Application $app) {
        return new EventCdbXMLImporter(
            new CdbXMLItemBaseImporter($app['udb2_media_iri_generator']),
            $app['udb2_event_cdbid_extractor'],
            new PriceDescriptionParser(
                new NumberFormatRepository(),
                new CurrencyRepository()
            )
        );
    }
);

$app['event_jsonld_projector'] = $app->share(
    function ($app) {
        $projector = new \CultuurNet\UDB3\Event\ReadModel\JSONLD\EventLDProjector(
            $app['event_jsonld_repository'],
            $app['event_iri_generator'],
            $app['event_service'],
            $app['place_service'],
            $app['organizer_service'],
            $app['media_object_serializer'],
            $app['iri_offer_identifier_factory'],
            $app['event_cdbxml_importer']
        );

        $projector->addDescriptionFilter(new \CultuurNet\UDB3\StringFilter\TidyStringFilter());
        $projector->addDescriptionFilter(new \CultuurNet\UDB3\StringFilter\StripSourceStringFilter());

        return $projector;
    }
);

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

$app['event_history_projector'] = $app->share(
    function ($app) {
        $projector = new \CultuurNet\UDB3\Event\ReadModel\History\HistoryProjector(
            $app['event_history_repository']
        );

        return $projector;
    }
);

$app['event_history_repository'] = $app->share(
    function ($app) {
        return new \CultuurNet\UDB3\Doctrine\Event\ReadModel\CacheDocumentRepository(
            $app['event_history_cache']
        );
    }
);

$app['event_history_cache'] = $app->share(
    function (Application $app) {
        return $app['cache']('event_history');
    }
);

$app['event_bus'] = $app->share(
    function ($app) {
        $eventBus = new \CultuurNet\UDB3\SimpleEventBus();

        $eventBus->beforeFirstPublication(function (\Broadway\EventHandling\EventBusInterface $eventBus) use ($app) {
            $subscribers = [
                'search_cache_manager',
                'event_relations_projector',
                'place_relations_projector',
                'event_jsonld_projector',
                'event_history_projector',
                'place_jsonld_projector',
                'organizer_jsonld_projector',
                'organizer_search_projector',
                'event_calendar_projector',
                'variations.search.projector',
                'variations.jsonld.projector',
                'index.projector',
                'event_permission.projector',
                'place_permission.projector',
                'amqp.publisher',
                'udb2_events_cdbxml_enricher',
                'udb2_actor_events_cdbxml_enricher',
                'udb2_events_to_udb3_place_applier',
                'udb2_events_to_udb3_event_applier',
                'udb2_actor_events_to_udb3_place_applier',
                'udb2_actor_events_to_udb3_organizer_applier',
                'udb2_label_importer',
                'place_permission.projector',
                LabelServiceProvider::JSON_PROJECTOR,
                LabelServiceProvider::RELATIONS_PROJECTOR,
                LabelServiceProvider::EVENT_LABEL_PROJECTOR,
                LabelServiceProvider::PLACE_LABEL_PROJECTOR,
                LabelServiceProvider::ORGANIZER_LABEL_PROJECTOR,
                LabelServiceProvider::RELATIONS_PROJECTOR,
                LabelServiceProvider::LABEL_ROLES_PROJECTOR,
                'role_detail_projector',
                'role_labels_projector',
                'label_roles_projector',
                'role_search_projector',
                'role_users_projector',
                'user_roles_projector',
                UserPermissionsServiceProvider::USER_PERMISSIONS_PROJECTOR,
            ];

            // Allow to override event bus subscribers through configuration.
            // The event replay command line utility uses this.
            if (isset($app['config']['event_bus']) &&
                isset($app['config']['event_bus']['subscribers'])) {

                $subscribers = $app['config']['event_bus']['subscribers'];
            }

            foreach ($subscribers as $subscriberServiceId) {
                $eventBus->subscribe($app[$subscriberServiceId]);
            }
        });

        return $eventBus;
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

$app['execution_context_metadata_enricher'] = $app->share(
    function ($app) {
        return new ExecutionContextMetadataEnricher();
    }
);

$app['event_stream_metadata_enricher'] = $app->share(
    function ($app) {
        $eventStreamDecorator = new \Broadway\EventSourcing\MetadataEnrichment\MetadataEnrichingEventStreamDecorator(
        );
        $eventStreamDecorator->registerEnricher(
            $app['execution_context_metadata_enricher']
        );
        return $eventStreamDecorator;
    }
);

$app['command_bus_event_dispatcher'] = $app->share(
    function ($app) {
        $dispatcher = new \Broadway\EventDispatcher\EventDispatcher();
        $dispatcher->addListener(
            \CultuurNet\UDB3\CommandHandling\ResqueCommandBus::EVENT_COMMAND_CONTEXT_SET,
            function ($context) use ($app) {
                /** @var ExecutionContextMetadataEnricher $metadataEnricher  */
                $metadataEnricher = $app['execution_context_metadata_enricher'];
                $metadataEnricher->setContext(
                    $context
                );
            }
        );

        return $dispatcher;
    }
);

$app['logger.command_bus'] = $app->share(
    function ($app) {
        $logger = new \Monolog\Logger('command_bus');

        $handlers = $app['config']['log.command_bus'];
        foreach ($handlers as $handler_config) {
            switch ($handler_config['type']) {
                case 'hipchat':
                    $handler = new \Monolog\Handler\HipChatHandler(
                        $handler_config['token'],
                        $handler_config['room']
                    );
                    break;
                case 'file':
                    $handler = new \Monolog\Handler\StreamHandler(
                        __DIR__ . '/web/' . $handler_config['path']
                    );
                    break;
                case 'socketioemitter':
                    $redisConfig = isset($handler_config['redis']) ? $handler_config['redis'] : array();
                    $redisConfig += array(
                        'host' => '127.0.0.1',
                        'port' => 6379,
                    );
                    if (extension_loaded('redis')) {
                        $redis = new \Redis();
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

                    $emitter = new \SocketIO\Emitter($redis);

                    if (isset($handler_config['namespace'])) {
                        $emitter->of($handler_config['namespace']);
                    }

                    if (isset($handler_config['room'])) {
                        $emitter->in($handler_config['room']);
                    }

                    $handler = new \CultuurNet\UDB3\Monolog\SocketIOEmitterHandler(
                        $emitter
                    );
                    break;
                default:
                    continue 2;
            }

            $handler->setLevel($handler_config['level']);
            $handler->pushProcessor(
                new \Monolog\Processor\PsrLogMessageProcessor()
            );

            $logger->pushHandler($handler);
        }

        return $logger;
    }
);

$app['event_command_bus_base'] = function (Application $app) {
    $mainCommandBus = new \CultuurNet\UDB3\CommandHandling\SimpleContextAwareCommandBus(
    );

    $commandBus = new \CultuurNet\UDB3\CommandHandling\ResqueCommandBus(
        $mainCommandBus,
        'event',
        $app['command_bus_event_dispatcher']
    );
    $commandBus->setLogger($app['logger.command_bus']);

    return $commandBus;
};

/**
 * "Event" command bus.
 */
$app['resque_command_bus_factory']('event');

/**
 * Tie command handlers to event command bus.
 */
$app->extend(
    'event_command_bus_out',
    function (CommandBusInterface $commandBus, Application $app) {
        // The order is important because the label first needs to be created
        // before it can be added.
        $commandBus->subscribe($app[LabelServiceProvider::COMMAND_HANDLER]);

        $commandBus->subscribe(
            new \CultuurNet\UDB3\Event\EventCommandHandler(
                $app['event_repository'],
                $app['organizer_repository'],
                $app[LabelServiceProvider::JSON_READ_REPOSITORY]
            )
        );

        $commandBus->subscribe(
            new \CultuurNet\UDB3\SavedSearches\SavedSearchesCommandHandler(
                $app['saved_searches_service_factory']
            )
        );

        $commandBus->subscribe(
            $app['variations.command_handler']
        );

        $commandBus->subscribe(
            new \CultuurNet\UDB3\Place\CommandHandler(
                $app['place_repository'],
                $app['organizer_repository'],
                $app[LabelServiceProvider::JSON_READ_REPOSITORY]
            )
        );

        $commandBus->subscribe(
            (new \CultuurNet\UDB3\Organizer\OrganizerCommandHandler(
                $app['organizer_repository'],
                $app[LabelServiceProvider::JSON_READ_REPOSITORY]
            ))
                ->withOrganizerRelationService($app['place_organizer_relation_service'])
                ->withOrganizerRelationService($app['event_organizer_relation_service'])
        );

        $commandBus->subscribe(
            new \CultuurNet\UDB3\Role\CommandHandler($app['real_role_repository'])
        );

        $commandBus->subscribe($app['media_manager']);

        return $commandBus;
    }
);

$app['used_labels_memory'] = $app->share(
    function ($app) {
        return new \CultuurNet\UDB3\UsedLabelsMemory\DefaultUsedLabelsMemoryService(
            new \CultuurNet\UDB3\UsedLabelsMemory\UsedLabelsMemoryRepository(
                $app['event_store'],
                $app['event_bus']
            )
        );
    }
);

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

$app['place_cdbxml_importer'] = $app->share(
    function (Application $app) {
        return new PlaceCdbXMLImporter(new CdbXMLItemBaseImporter($app['udb2_media_iri_generator']));
    }
);

$app['place_jsonld_projector'] = $app->share(
    function ($app) {
        $projector = new \CultuurNet\UDB3\Place\ReadModel\JSONLD\PlaceLDProjector(
            $app['place_jsonld_repository'],
            $app['place_iri_generator'],
            $app['organizer_service'],
            $app['media_object_serializer'],
            $app['place_cdbxml_importer']
        );

        return $projector;
    }
);

$app['place_jsonld_repository'] = $app->share(
    function ($app) {
        $repository = new \CultuurNet\UDB3\Doctrine\Event\ReadModel\CacheDocumentRepository(
            $app['place_jsonld_cache']
        );

        return new \CultuurNet\UDB3\ReadModel\BroadcastingDocumentRepositoryDecorator(
            $repository,
            $app['event_bus'],
            new \CultuurNet\UDB3\Place\ReadModel\JSONLD\EventFactory(
                $app['place_iri_generator']
            )
        );
    }
);

$app['place_jsonld_cache'] = $app->share(
    function ($app) {
        return $app['cache']('place_jsonld');
    }
);

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
        return new \Broadway\EventStore\DBALEventStore(
            $app['dbal_connection'],
            $app['eventstore_payload_serializer'],
            new \Broadway\Serializer\SimpleInterfaceSerializer(),
            'places'
        );
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
        $service = new \CultuurNet\UDB3\PlaceService(
            $app['place_jsonld_repository'],
            $app['place_repository'],
            $app['place_iri_generator']
        );

        return $service;
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
            $app['organizer_repository'],
            $app['labels.constraint_aware_service']
        );
    }
);

$app['organizer_jsonld_projector'] = $app->share(
    function ($app) {
        return new \CultuurNet\UDB3\Organizer\OrganizerLDProjector(
            $app['organizer_jsonld_repository'],
            $app['organizer_iri_generator'],
            $app['event_bus'],
            $app[LabelServiceProvider::JSON_READ_REPOSITORY]
        );
    }
);

$app['real_organizer_jsonld_repository'] = $app->share(
    function ($app) {
        return new \CultuurNet\UDB3\Doctrine\Event\ReadModel\CacheDocumentRepository(
            $app['organizer_jsonld_cache']
        );
    }
);

$app['organizer_jsonld_repository'] = $app->share(
    function ($app) {
        return new \CultuurNet\UDB3\ReadModel\BroadcastingDocumentRepositoryDecorator(
            $app['real_organizer_jsonld_repository'],
            $app['event_bus'],
            new \CultuurNet\UDB3\Organizer\ReadModel\JSONLD\EventFactory()
        );
    }
);

$app['organizer_jsonld_cache'] = $app->share(
    function ($app) {
        return $app['cache']('organizer_jsonld');
    }
);

$app['eventstore_payload_serializer'] = $app->share(
    function ($app) {
        return \CultuurNet\UDB3\BackwardsCompatiblePayloadSerializerFactory::createSerializer(
            $app[LabelServiceProvider::JSON_READ_REPOSITORY]
        );
    }
);

$app['organizer_store'] = $app->share(
    function ($app) {
        $eventStore = new \Broadway\EventStore\DBALEventStore(
            $app['dbal_connection'],
            $app['eventstore_payload_serializer'],
            new \Broadway\Serializer\SimpleInterfaceSerializer(),
            'organizers'
        );

        return new UniqueDBALEventStoreDecorator(
            $eventStore,
            $app['dbal_connection'],
            new StringLiteral('organizer_unique_websites'),
            new WebsiteUniqueConstraintService()
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
        return new \Broadway\EventStore\DBALEventStore(
            $app['dbal_connection'],
            $app['eventstore_payload_serializer'],
            new \Broadway\Serializer\SimpleInterfaceSerializer(),
            'roles'
        );
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
            new \CultuurNet\UDB3\Doctrine\Event\ReadModel\CacheDocumentRepository(
                $app['role_detail_cache']
            ),
            $app['event_bus'],
            new \CultuurNet\UDB3\Role\ReadModel\Detail\EventFactory()
        );
    }
);

$app['user_roles_repository'] = $app->share(
    function ($app) {
        return new \CultuurNet\UDB3\Doctrine\Event\ReadModel\CacheDocumentRepository(
            $app['user_roles_cache']
        );
    }
);

$app['role_search_repository.table_name'] = new StringLiteral('roles_search');

$app['role_search_repository'] = $app->share(
    function ($app) {
        return new \CultuurNet\UDB3\Role\ReadModel\Search\Doctrine\DBALRepository(
            $app['dbal_connection'],
            $app['role_search_repository.table_name']
        );
    }
);

$app['role_search_projector'] = $app->share(
    function ($app) {
        return new \CultuurNet\UDB3\Role\ReadModel\Search\Projector(
            $app['role_search_repository']
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
        return new \CultuurNet\UDB3\Doctrine\Event\ReadModel\CacheDocumentRepository(
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
        return new \CultuurNet\UDB3\Doctrine\Event\ReadModel\CacheDocumentRepository(
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
        return new \CultuurNet\UDB3\Doctrine\Event\ReadModel\CacheDocumentRepository(
            $app['role_users_cache']
        );
    }
);

$app['role_users_projector'] = $app->share(
    function ($app) {
        return new \CultuurNet\UDB3\Role\ReadModel\Users\RoleUsersProjector(
            $app['role_users_read_repository'],
            $app['user_identity_resolver']
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

$app['event_export'] = $app->share(
    function ($app) {
        $service = new \CultuurNet\UDB3\EventExport\EventExportService(
            $app['personal_variation_decorated_event_service'],
            $app['search_service'],
            new \Broadway\UuidGenerator\Rfc4122\Version4Generator(),
            realpath(__DIR__ .  '/web/downloads'),
            new CallableIriGenerator(
                function ($fileName) use ($app) {
                    return $app['config']['url'] . '/downloads/' . $fileName;
                }
            ),
            new \CultuurNet\UDB3\EventExport\Notification\Swift\NotificationMailer(
                $app['mailer'],
                $app['event_export_notification_mail_factory']
            ),
            $app['search_results_generator']
        );

        return $service;
    }
);

$app['amqp-execution-delay'] = isset($app['config']['amqp_execution_delay']) ?
    Natural::fromNative($app['config']['amqp_execution_delay']) :
    Natural::fromNative(10);

$app['logger.amqp.event_bus_forwarder'] = $app->share(
    function (Application $app) {
        $logger = new Monolog\Logger('amqp.event_bus_forwarder');
        $logger->pushHandler(new \Monolog\Handler\StreamHandler('php://stdout'));

        $logFileHandler = new \Monolog\Handler\StreamHandler(
            __DIR__ . '/log/amqp.log',
            \Monolog\Logger::DEBUG
        );
        $logger->pushHandler($logFileHandler);

        return $logger;
    }
);

$app['uitpas'] = $app->share(
    function (Application $app) {
        /** @var CultureFeed $culturefeed */
        $cultureFeed = $app['culturefeed'];
        return $cultureFeed->uitpas();
    }
);

$app['logger.uitpas'] = $app->share(
    function (Application $app) {
        $logger = new Monolog\Logger('uitpas');
        $logger->pushHandler(new \Monolog\Handler\StreamHandler(__DIR__ . '/log/uitpas.log'));

        return $logger;
    }
);

// This service is used by the background worker to impersonate the user
// who initially queued the command.
$app['impersonator'] = $app->share(
    function () {
        return new \CultuurNet\UDB3\Silex\Impersonator();
    }
);

$app['database.installer'] = $app->share(
    function (Application $app) {
        return new \CultuurNet\UDB3\Silex\DatabaseSchemaInstaller($app);
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
    new \CultuurNet\SilexAMQP\AMQPConnectionServiceProvider(),
    [
        'amqp.connection.host' => $app['config']['amqp']['host'],
        'amqp.connection.port' => $app['config']['amqp']['port'],
        'amqp.connection.user' => $app['config']['amqp']['user'],
        'amqp.connection.password' => $app['config']['amqp']['password'],
        'amqp.connection.vhost' => $app['config']['amqp']['vhost'],
    ]
);

$app->register(
    new \CultuurNet\SilexAMQP\AMQPPublisherServiceProvider(),
    [
        'amqp.publisher.content_type_map' => $app['amqp.content_type_map'],
        'amqp.publisher.exchange_name' => $app['config']['amqp']['publish']['udb3']['exchange'],
    ]
);

$app->register(
    new \CultuurNet\UDB3\Silex\Search\ElasticSearchServiceProvider(),
    [
        'elasticsearch.host' => $app['config']['elasticsearch']['host'],
    ]
);

$app->register(
    new \CultuurNet\UDB3\Silex\Organizer\OrganizerElasticSearchServiceProvider(),
    [
        'elasticsearch.organizer.index_name' => $app['config']['elasticsearch']['organizer']['index_name'],
        'elasticsearch.organizer.document_type' => $app['config']['elasticsearch']['organizer']['document_type'],
    ]
);

$app->register(new \CultuurNet\UDB3\Silex\Export\ExportServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\IndexServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\Event\EventEditingServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\Place\PlaceEditingServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\Place\PlaceLookupServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\Organizer\OrganizerServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\User\UserServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\Event\EventPermissionServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\Place\PlacePermissionServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\Offer\OfferServiceProvider());
$app->register(new LabelServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\Role\RoleEditingServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\Role\RoleReadingServiceProvider());
$app->register(new UserPermissionsServiceProvider());

$app->register(
    new \CultuurNet\UDB3\Silex\DoctrineMigrationsServiceProvider(),
    ['migrations.config_file' => __DIR__ . '/migrations.yml']
);

// Add the oauth service provider.
$app->register(new OAuthServiceProvider(), array(
    'oauth.fetcher.base_url' => $app['config']['oauth']['base_url'],
    'oauth.fetcher.consumer' => $app['config']['oauth']['consumer'],
));

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

$app['predis.client'] = $app->share(function ($app) {
    $redisURI = isset($app['config']['redis']['uri']) ?
        $app['config']['redis']['uri'] : 'tcp://127.0.0.1:6379';

    return new Predis\Client($redisURI);
});

$app['oauth.model.provider.nonce_provider'] = $app->share(function (Application $app) {
    return new NonceProvider(
        $app['predis.client']
    );
});

$app->extend(
    'oauth.model.provider.token_provider',
    function (TokenProviderInterface $tokenProvider, Application $app) {
        return new TokenProviderCache($tokenProvider, $app['predis.client']);
    }
);

$app['entryapi.link_base_url'] = $app->share(function (Application $app) {
    return $app['config']['entryapi']['link_base_url'];
});

$app['cdbxml_proxy'] = $app->share(
    function ($app) {
        $accept = new StringLiteral(
            $app['config']['cdbxml_proxy']['accept']
        );

        /** @var \ValueObjects\Web\Hostname $redirectDomain */
        $redirectDomain = \ValueObjects\Web\Hostname::fromNative(
            $app['config']['cdbxml_proxy']['redirect_domain']
        );

        /** @var \ValueObjects\Web\Hostname $redirectDomain */
        $redirectPort = \ValueObjects\Web\PortNumber::fromNative(
            $app['config']['cdbxml_proxy']['redirect_port']
        );

        return new \CultuurNet\UDB3\Symfony\Proxy\CdbXmlProxy(
            $accept,
            $redirectDomain,
            $redirectPort,
            new \Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory(),
            new \Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory(),
            new \GuzzleHttp\Client()
        );
    }
);

$app->register(new \CultuurNet\UDB3\Silex\Search\SAPISearchServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\Offer\BulkLabelOfferServiceProvider());

$app->register(
    new \TwoDotsTwice\SilexFeatureToggles\FeatureTogglesProvider(
        isset($app['config']['toggles']) ? $app['config']['toggles'] : []
    )
);

$app->register(
    new \CultuurNet\UDB3\Silex\UDB2IncomingEventServicesProvider(),
    [
        'udb2_place_external_id_mapping.yml_file_location' => $udb3ConfigLocation . '/external_id_mapping_place.yml',
        'udb2_organizer_external_id_mapping.yml_file_location' => $udb3ConfigLocation . '/external_id_mapping_organizer.yml',
        'udb2_cdbxml_enricher.http_response_timeout' => isset($app['config']['udb2_cdbxml_enricher']['http_response_timeout']) ? $app['config']['udb2_cdbxml_enricher']['http_response_timeout'] : 3,
        'udb2_cdbxml_enricher.http_connect_timeout' => isset($app['config']['udb2_cdbxml_enricher']['http_connect_timeout']) ? $app['config']['udb2_cdbxml_enricher']['http_connect_timeout'] : 1,
    ]
);

return $app;

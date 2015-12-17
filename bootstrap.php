<?php

require_once __DIR__ . '/vendor/autoload.php';

use CultuurNet\SilexServiceProviderOAuth\OAuthServiceProvider;
use CultuurNet\SymfonySecurityOAuth\Model\Provider\TokenProviderInterface;
use CultuurNet\SymfonySecurityOAuthRedis\NonceProvider;
use CultuurNet\SymfonySecurityOAuthRedis\TokenProviderCache;
use Guzzle\Log\ClosureLogAdapter;
use Guzzle\Plugin\Log\LogPlugin;
use Silex\Application;
use CultuurNet\UDB3\SearchAPI2\DefaultSearchService as SearchAPI2;
use CultuurNet\UDB3\SearchAPI2\Filters\UDB3Place as UDB3PlaceFilter;
use CultuurNet\UDB3\SearchAPI2\FilteredSearchService;
use DerAlex\Silex\YamlConfigServiceProvider;
use CultuurNet\UDB3\Search\PullParsingSearchService;
use CultuurNet\UDB3\Search\CachedDefaultSearchService;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use JDesrosiers\Silex\Provider\CorsServiceProvider;
use ValueObjects\String\String;

$app = new Application();

$app['debug'] = true;

$app->register(new YamlConfigServiceProvider(__DIR__ . '/config.yml'));

$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

/**
 * Session service.
 */
$app->register(new Silex\Provider\SessionServiceProvider());
$app->register(new CultuurNet\UiTIDProvider\Session\SessionConfigurationProvider());

$app->register(new \CultuurNet\UDB3\Silex\SavedSearchesServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\VariationsServiceProvider());
$app->register(new Silex\Provider\ServiceControllerServiceProvider());

$app->register(new CorsServiceProvider(), array(
    "cors.allowOrigin" => implode(" ", $app['config']['cors']['origins']),
    "cors.allowCredentials" => true
));

/**
 * UiTID Authentication services.
 */
$app->register(new CultuurNet\UiTIDProvider\Auth\AuthServiceProvider());

/**
 * Security services.
 */
$app->register(new \CultuurNet\UiTIDProvider\Security\UiTIDSecurityServiceProvider());

/**
 * CultureFeed services.
 */
$app->register(
  new \CultuurNet\UiTIDProvider\CultureFeed\CultureFeedServiceProvider(),
  array(
    'culturefeed.endpoint' => $app['config']['uitid']['base_url'],
    'culturefeed.consumer.key' => $app['config']['uitid']['consumer']['key'],
    'culturefeed.consumer.secret' => $app['config']['uitid']['consumer']['secret'],
  )
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

$app['iri_generator'] = $app->share(
    function ($app) {
        return new CallableIriGenerator(
            function ($cdbid) use ($app) {
                return $app['config']['url'] . '/event/' . $cdbid;
            }
        );
    }
);

$app['place_iri_generator'] = $app->share(
    function ($app) {
        return new CallableIriGenerator(
            function ($cdbid) use ($app) {
                return $app['config']['url'] . '/place/' . $cdbid;
            }
        );
    }
);

$app['organizer_iri_generator'] = $app->share(
    function ($app) {
        return new CallableIriGenerator(
            function ($cdbid) use ($app) {
                return $app['config']['url'] . '/organizer/' . $cdbid;
            }
        );
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

$app['search_api_2'] = $app->share(
    function ($app) {
        $searchApiUrl =
            $app['config']['uitid']['base_url'] .
            $app['config']['uitid']['apis']['search'];

        return new SearchAPI2(
            $searchApiUrl,
            $app['uitid_consumer_credentials']
        );
    }
);

$app['filtered_search_api_2'] = $app->share(
    function ($app) {
        $filteredSearchService = new FilteredSearchService(
            $app['search_api_2']
        );
        $filteredSearchService->filter(new UDB3PlaceFilter());
        return $filteredSearchService;
    }
);

$app['search_service'] = $app->share(
    function ($app) {
        return new PullParsingSearchService(
            $app['filtered_search_api_2'],
            $app['iri_generator']
        );
    }
);

$app['cached_search_service'] = $app->share(
    function ($app) {
        return new CachedDefaultSearchService(
            $app['search_service'],
            $app['cache']('default_search')
        );
    }
);

$app['search_cache_manager'] = $app->share(
    function (Application $app) {
        $parameters = $app['config']['cache']['redis'];

        return new \CultuurNet\UDB3\Search\Cache\CacheManager(
            $app['cached_search_service'],
            new Predis\Client($parameters)
        );
    }
);

$app['search_cache_manager'] = $app->extend(
    'search_cache_manager',
    function(\CultuurNet\UDB3\Search\Cache\CacheManager $manager, Application $app) {
        $logger = new \Monolog\Logger('search_cache_manager');
        $logger->pushHandler(
            new \Monolog\Handler\StreamHandler(__DIR__ . '/log/search_cache_manager.log')
        );
        $manager->setLogger($logger);

        return $manager;
    }
);

$app['event_service'] = $app->share(
    function ($app) {
        $service = new \CultuurNet\UDB3\LocalEventService(
            $app['event_jsonld_repository'],
            $app['event_repository'],
            $app['event_relations_repository'],
            $app['iri_generator']
        );

        return $service;
    }
);

$app['personal_variation_decorated_event_service'] = $app->share(
    function (Application $app) {
        $decoratedService = $app['event_service'];
        $session = $app['session'];

        /** @var \CultuurNet\Auth\User $user */
        $user = $session->get('culturefeed_user');
        $criteria = (new \CultuurNet\UDB3\Variations\ReadModel\Search\Criteria())
            ->withPurpose(
                new \CultuurNet\UDB3\Variations\Model\Properties\Purpose('personal')
            )
            ->withOwnerId(
                new \CultuurNet\UDB3\Variations\Model\Properties\OwnerId(
                    $user->getId()
                )
            );

        return new \CultuurNet\UDB3\Variations\VariationDecoratedEventService(
            $decoratedService,
            $app['variations.search'],
            $criteria,
            $app['variations.jsonld_repository'],
            $app['iri_generator']
        );
    }
);

/**
 * UiTID User services.
 */
$app->register(new CultuurNet\UiTIDProvider\User\UserServiceProvider());

$app['current_user'] = $app['uitid_user'];

$app['auth_service'] = $app->share(
    function ($app) {
        $uitidConfig = $app['config']['uitid'];

        return new CultuurNet\Auth\Guzzle\Service(
            $uitidConfig['base_url'],
            $app['uitid_consumer_credentials']
        );
    }
);

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

        $broadcastingRepository = new \CultuurNet\UDB3\Event\ReadModel\BroadcastingDocumentRepositoryDecorator(
            $cachedRepository,
            $app['event_bus'],
            new \CultuurNet\UDB3\Event\ReadModel\JSONLD\EventFactory()
        );

        return $broadcastingRepository;
    }
);

$app['event_jsonld_cache'] = $app->share(
    function (Application $app) {
        return $app['cache']('event_jsonld');
    }
);

$app['event_jsonld_projector'] = $app->share(
    function ($app) {
        $projector = new \CultuurNet\UDB3\Event\EventLDProjector(
            $app['event_jsonld_repository'],
            $app['iri_generator'],
            $app['event_service'],
            $app['place_service'],
            $app['organizer_service']
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

$app['relations_projector'] = $app->share(
    function ($app) {
        return new \CultuurNet\UDB3\Event\ReadModel\Relations\Projector(
            $app['event_relations_repository']
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

/**
 * Factory method for instantiating a UDB2 related logger with the specified
 * name.
 */
$app['udb2_logger'] = $app->share(
    function (Application $app) {
        return function ($name) use ($app) {
            $logger = new \Monolog\Logger(
                $name,
                [
                    $app['udb2_log_handler']
                ]
            );

            return $logger;
        };
    }
);

$app['event_bus'] = $app->share(
    function ($app) {
        $eventBus = new \CultuurNet\UDB3\SimpleEventBus();

        $eventBus->beforeFirstPublication(function (\Broadway\EventHandling\EventBusInterface $eventBus) use ($app) {
            $subscribers = [
                'search_cache_manager',
                'relations_projector',
                'event_jsonld_projector',
                'udb2_event_importer',
                'event_history_projector',
                'place_jsonld_projector',
                'organizer_jsonld_projector',
                'event_calendar_projector',
                'variations.search.projector',
                'variations.jsonld.projector',
                'index.projector',
                'event_permission.projector',
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

$app['udb2_entry_api_improved_factory'] = $app->share(
    function ($app) {
        $uitidConfig = $app['config']['uitid'];
        $baseUrl =
            $uitidConfig['base_url'] .
            $uitidConfig['apis']['entry'];

        return new \CultuurNet\UDB3\UDB2\EntryAPIImprovedFactory(
            new \CultuurNet\UDB3\UDB2\Consumer(
                $baseUrl,
                $app['uitid_consumer_credentials']
            )
        );
    }
);

$app->extend(
    'udb2_entry_api_improved_factory',
    function (
        \CultuurNet\UDB3\UDB2\EntryAPIImprovedFactoryInterface $factory,
        Application $app
    ) {
        $file = __DIR__ . '/log/entryapi.log';

        $format = "\n\n# Request:\n{request}\n\n# Response:\n{response}\n\n# Errors: {curl_code} {curl_error}\n\n";
        $logAdapter = new \Guzzle\Log\ClosureLogAdapter(function ($message, $priority, $extra) use ($file) {
            file_put_contents($file, $message, FILE_APPEND);
        });

        return new \CultuurNet\UDB3\UDB2\EventSubscriberDecoratedEntryAPIImprovedFactory(
            $factory,
            new \Guzzle\Plugin\Log\LogPlugin(
                $logAdapter,
                $format
            )
        );
    }
);

$app->extend(
    'udb2_entry_api_improved_factory',
    function (
        \CultuurNet\UDB3\UDB2\EntryAPIImprovedFactoryInterface $factory,
        Application $app
    ) {
        // Print request and response for debugging purposes. Only on CLI.
        if (PHP_SAPI === 'cli') {
            $adapter = new ClosureLogAdapter(
                function ($message, $priority, $extras) {
                    print $message;
                }
            );

            $format = "\n\n# Request:\n{request}\n\n# Response:\n{response}\n\n# Errors: {curl_code} {curl_error}\n\n";
            $log = new LogPlugin($adapter, $format);

            return new \CultuurNet\UDB3\UDB2\EventSubscriberDecoratedEntryAPIImprovedFactory(
                $factory,
                $log
            );
        } else {
            return $factory;
        }
    }
);

$app['real_event_repository'] = $app->share(
  function ($app) {
      $repository = new \CultuurNet\UDB3\Event\EventRepository(
          $app['event_store'],
          $app['event_bus'],
          [
              $app['event_stream_metadata_enricher']
          ]
      );

      return $repository;
  }
);

$app['udb2_event_cdbxml'] = $app->share(
    function (Application $app) {
        $uitidConfig = $app['config']['uitid'];
        $baseUrl = $uitidConfig['base_url'] . $uitidConfig['apis']['entry'];

        $userId = new String($uitidConfig['impersonation_user_id']);

        $cdbXmlFromEntryAPI = new \CultuurNet\UDB3\UDB2\EventCdbXmlFromEntryAPI(
            $baseUrl,
            $app['uitid_consumer_credentials'],
            $userId,
            // @todo Move the cdbxml version to configuration file. Use the same
            // setting when instantiating the ImprovedEntryApiFactory.
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL'
        );

        $labeledAsUDB3Place = new \CultuurNet\UDB3\UDB2\LabeledAsUDB3Place();

        return new \CultuurNet\UDB3\UDB2\Event\SpecificationDecoratedEventCdbXml(
            $cdbXmlFromEntryAPI,
            new \CultuurNet\UDB3\Cdb\Event\Not($labeledAsUDB3Place)
        );
    }
);

$app['udb2_event_importer'] = $app->share(
    function (Application $app) {
        $logger = new \Monolog\Logger('udb2-event-importer');

        $logger->pushHandler($app['udb2_log_handler']);

        $importer = new \CultuurNet\UDB3\UDB2\EventImporter(
            $app['udb2_event_cdbxml'],
            $app['real_event_repository'],
            $app['place_service'],
            $app['organizer_service']
        );

        $importer->setLogger($logger);

        return $importer;
    }
);

$app['event_repository'] = $app->share(
    function ($app) {
        $repository = $app['real_event_repository'];

        $udb2RepositoryDecorator = new \CultuurNet\UDB3\UDB2\EventRepository(
            $repository,
            $app['udb2_entry_api_improved_factory'],
            $app['udb2_event_importer'],
            $app['place_service'],
            $app['organizer_service'],
            array($app['event_stream_metadata_enricher'])
        );

        if (true == $app['config']['sync_with_udb2']) {
            $udb2RepositoryDecorator->syncBackOn();
        }
        return $udb2RepositoryDecorator;
    }
);

$app['execution_context_metadata_enricher'] = $app->share(
    function ($app) {
        return new \CultuurNet\UDB3\EventSourcing\ExecutionContextMetadataEnricher(
        );
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
                $app['execution_context_metadata_enricher']->setContext(
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
 * Command bus serving command publishers.
 */
$app['event_command_bus'] = $app->share(
    function ($app) {
        $commandBus = $app['event_command_bus_base'];

        return new \CultuurNet\UDB3\Silex\ContextDecoratedCommandBus(
            $commandBus,
            $app
        );
    }
);

/**
 * Command bus serving command handlers.
 */
$app['event_command_bus_out'] = $app->share(
    function (Application $app) {
        $commandBus = $app['event_command_bus_base'];

        $commandBus->subscribe(
            new \CultuurNet\UDB3\Event\EventCommandHandler(
                $app['event_repository'],
                $app['search_service']
            )
        );

        $eventInfoService = new \CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\EventInfo\CultureFeedEventInfoService(
            $app['uitpas'],
            new \CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\Promotion\EventOrganizerPromotionQueryFactory(
                $app['clock']
            )
        );
        $eventInfoService->setLogger($app['logger.uitpas']);
        $commandBus->subscribe(
            new \CultuurNet\UDB3\EventExport\EventExportCommandHandler(
                $app['event_export'],
                $app['config']['prince']['binary'],
                $eventInfoService,
                $app['event_calendar_repository']
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
                $app['place_repository']
            )
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

$app['event_labeller'] = $app->share(
    function ($app) {
        return new \CultuurNet\UDB3\Event\DefaultEventLabellerService(
            $app['event_service'],
            $app['event_command_bus']
        );
    }
);

$app['event_editor'] = $app->share(
    function ($app) {
        return new \CultuurNet\UDB3\Event\DefaultEventEditingService(
            $app['event_service'],
            $app['event_command_bus'],
            new \Broadway\UuidGenerator\Rfc4122\Version4Generator(),
            $app['event_repository'],
            $app['place_service']
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

$app['place_jsonld_projector'] = $app->share(
    function ($app) {
        $projector = new \CultuurNet\UDB3\Place\PlaceLDProjector(
            $app['place_jsonld_repository'],
            $app['place_iri_generator'],
            $app['organizer_service']
        );

        return $projector;
    }
);

$app['place_jsonld_repository'] = $app->share(
    function ($app) {
        $repository = new \CultuurNet\UDB3\Doctrine\Event\ReadModel\CacheDocumentRepository(
            $app['place_jsonld_cache']
        );

        return new \CultuurNet\UDB3\Event\ReadModel\BroadcastingDocumentRepositoryDecorator(
            $repository,
            $app['event_bus'],
            new \CultuurNet\UDB3\Place\ReadModel\JSONLD\EventFactory()
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

$app['udb2_log_handler'] = $app->share(
    function (Application $app) {
        return new \Monolog\Handler\StreamHandler(__DIR__ . '/log/udb2.log');
    }
);

$app['udb2_actor_cdbxml_provider'] = $app->share(
    function (Application $app) {
        $cdbXmlService = new \CultuurNet\UDB3\UDB2\ActorCdbXmlFromSearchService(
            $app['search_api_2']
        );

        return $cdbXmlService;
    }
);

$app['udb2_place_importer'] = $app->share(
    function (Application $app) {
        $importer = new \CultuurNet\UDB3\UDB2\Place\PlaceCdbXmlImporter(
            $app['udb2_actor_cdbxml_provider'],
            $app['real_place_repository']
        );

        $logger = new \Monolog\Logger('udb2-place-importer');
        $logger->pushHandler($app['udb2_log_handler']);

        $importer->setLogger($logger);

        return $importer;
    }
);

$app['real_place_repository'] = $app->share(
    function (Application $app) {
        $repository = new \CultuurNet\UDB3\Place\PlaceRepository(
            $app['place_store'],
            $app['event_bus'],
            array($app['event_stream_metadata_enricher'])
        );

        return $repository;
    }
);

$app['place_repository'] = $app->share(
    function ($app) {
        $udb2RepositoryDecorator = new \CultuurNet\UDB3\UDB2\Place\PlaceRepository(
            $app['real_place_repository'],
            $app['udb2_entry_api_improved_factory'],
            $app['udb2_place_importer'],
            $app['organizer_service'],
            array($app['event_stream_metadata_enricher'])
        );

        if (true == $app['config']['sync_with_udb2']) {
            $udb2RepositoryDecorator->syncBackOn();
        }
        return $udb2RepositoryDecorator;
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

$app['place_editing_service'] = $app->share(
    function ($app) {
        return new CultuurNet\UDB3\Place\DefaultPlaceEditingService(
            $app['event_command_bus'],
            new Broadway\UuidGenerator\Rfc4122\Version4Generator(),
            $app['place_repository']
        );
    }
);

/** Organizer **/

$app['organizer_iri_generator'] = $app->share(
    function ($app) {
        return new CallableIriGenerator(
            function ($cdbid) use ($app) {
                return $app['config']['url'] . '/organizer/' . $cdbid;
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

$app['organizer_jsonld_projector'] = $app->share(
  function ($app) {
      return new \CultuurNet\UDB3\Organizer\OrganizerLDProjector(
          $app['organizer_jsonld_repository'],
          $app['organizer_iri_generator'],
          $app['event_bus']
      );
  }
);

$app['organizer_jsonld_repository'] = $app->share(
    function ($app) {
        return new \CultuurNet\UDB3\Doctrine\Event\ReadModel\CacheDocumentRepository(
            $app['organizer_jsonld_cache']
        );
    }
);

$app['organizer_jsonld_cache'] = $app->share(
    function ($app) {
        return $app['cache']('organizer_jsonld');
    }
);

$app['eventstore_payload_serializer'] = $app->share(
    function () {
        return \CultuurNet\UDB3\BackwardsCompatiblePayloadSerializerFactory::createSerializer();
    }
);

$app['organizer_store'] = $app->share(
    function ($app) {
        return new \Broadway\EventStore\DBALEventStore(
            $app['dbal_connection'],
            $app['eventstore_payload_serializer'],
            new \Broadway\Serializer\SimpleInterfaceSerializer(),
            'organizers'
        );
    }
);

$app['udb2_organizer_importer'] = $app->share(
    function (Application $app) {
        $importer = new \CultuurNet\UDB3\UDB2\Organizer\OrganizerCdbXmlImporter(
            $app['udb2_actor_cdbxml_provider'],
            $app['real_organizer_repository']
        );

        $logger = new \Monolog\Logger('udb2-organizer-importer');
        $logger->pushHandler($app['udb2_log_handler']);

        $importer->setLogger($logger);

        return $importer;
    }
);

$app['real_organizer_repository'] = $app->share(
    function (Application $app) {
        $repository = new \CultuurNet\UDB3\Organizer\OrganizerRepository(
            $app['organizer_store'],
            $app['event_bus'],
            array($app['event_stream_metadata_enricher'])
        );

        return $repository;
    }
);

$app['organizer_repository'] = $app->share(
    function ($app) {
        $udb2RepositoryDecorator = new \CultuurNet\UDB3\UDB2\Organizer\OrganizerRepository(
            $app['real_organizer_repository'],
            $app['udb2_entry_api_improved_factory'],
            $app['udb2_organizer_importer'],
            array($app['event_stream_metadata_enricher'])
        );

        if (true == $app['config']['sync_with_udb2']) {
            $udb2RepositoryDecorator->syncBackOn();
        }
        return $udb2RepositoryDecorator;
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
            )
        );

        return $service;
    }
);

$app['amqp-execution-delay'] = 4;

$app['amqp-connection'] = $app->share(
    function (Application $app) {
        $amqpConfig = $host = $app['config']['amqp'];
        $connection = new \PhpAmqpLib\Connection\AMQPStreamConnection(
            $amqpConfig['host'],
            $amqpConfig['port'],
            $amqpConfig['user'],
            $amqpConfig['password'],
            $amqpConfig['vhost']
        );

        $deserializerLocator = new \CultuurNet\Deserializer\SimpleDeserializerLocator();
        $deserializerLocator->registerDeserializer(
            new String(
                'application/vnd.cultuurnet.udb2-events.event-created+json'
            ),
            new \CultuurNet\UDB2DomainEvents\EventCreatedJSONDeserializer()
        );
        $deserializerLocator->registerDeserializer(
            new String(
                'application/vnd.cultuurnet.udb2-events.event-updated+json'
            ),
            new \CultuurNet\UDB2DomainEvents\EventUpdatedJSONDeserializer()
        );

        $consumeConfig = $amqpConfig['consume']['udb2'];

        // Delay the consumption of UDB2 updates with some seconds to prevent a
        // race condition with the UDB3 worker. Modifications initiated by
        // commands in the UDB3 queue worker need to finish before their
        // counterpart UDB2 update is processed.
        $delay = $app['amqp-execution-delay'];

        $eventBusForwardingConsumer = new \CultuurNet\UDB3\UDB2\AMQP\EventBusForwardingConsumer(
            $connection,
            $app['event_bus'],
            $deserializerLocator,
            new String($amqpConfig['consumer_tag']),
            new String($consumeConfig['exchange']),
            new String($consumeConfig['queue']),
            $delay
        );

        $logger = new Monolog\Logger('amqp.event_bus_forwarder');
        $logger->pushHandler(new \Monolog\Handler\StreamHandler('php://stdout'));

        $logFileHandler = new \Monolog\Handler\StreamHandler(
            __DIR__ . '/log/amqp.log',
            \Monolog\Logger::DEBUG
        );
        $logger->pushHandler($logFileHandler);
        $eventBusForwardingConsumer->setLogger($logger);

        return $connection;
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
    function (Application $app) {
        return new \CultuurNet\UDB3\Silex\Impersonator(
            $app['session']
        );
    }
);

$app['database.installer'] = $app->share(
    function (Application $app) {
        return new \CultuurNet\UDB3\Silex\DatabaseSchemaInstaller($app);
    }
);

$app->register(new \CultuurNet\UDB3\Silex\IndexServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\PlaceLookupServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\OrganizerLookupServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\UsersServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\PermissionServiceProvider());

$app->register(
    new \CultuurNet\UDB3\Silex\DoctrineMigrationsServiceProvider(),
    ['migrations.config_file' => __DIR__ . '/migrations.yml']
);

// Add the oauth service provider.
$app->register(new OAuthServiceProvider(), array(
    'oauth.fetcher.base_url' => $app['config']['oauth']['base_url'],
    'oauth.fetcher.consumer' => $app['config']['oauth']['consumer'],
));

$app->register(new \CultuurNet\UDB3\Silex\Media\MediaServiceProvider());

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

return $app;

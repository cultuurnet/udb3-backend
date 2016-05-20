<?php
use CultuurNet\BroadwayAMQP\EventBusForwardingConsumerFactory;
use CultuurNet\Deserializer\SimpleDeserializerLocator;
use CultuurNet\SilexServiceProviderOAuth\OAuthServiceProvider;
use CultuurNet\SymfonySecurityJwt\Authentication\JwtUserToken;
use CultuurNet\SymfonySecurityOAuth\Model\Provider\TokenProviderInterface;
use CultuurNet\SymfonySecurityOAuth\Security\OAuthToken;
use CultuurNet\SymfonySecurityOAuthRedis\NonceProvider;
use CultuurNet\SymfonySecurityOAuthRedis\TokenProviderCache;
use CultuurNet\UDB3\Offer\OfferLocator;
use CultuurNet\UDB3\ReadModel\Index\EntityIriGeneratorFactory;
use CultuurNet\UDB3\Silex\CultureFeed\CultureFeedServiceProvider;
use CultuurNet\UDB3\Silex\Impersonator;
use Guzzle\Log\ClosureLogAdapter;
use Guzzle\Plugin\Log\LogPlugin;
use PhpAmqpLib\Connection\AMQPStreamConnection;
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

$app->register(new CorsServiceProvider(), array(
    "cors.allowOrigin" => implode(" ", $app['config']['cors']['origins']),
    "cors.allowCredentials" => true
));

$app['local_domain'] = \ValueObjects\Web\Domain::specifyType(
    parse_url($app['config']['url'])['host']
);

$app['udb2_domain'] = \ValueObjects\Web\Domain::specifyType('uitdatabank.be');

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

$app['event_service'] = $app->share(
    function ($app) {
        $service = new \CultuurNet\UDB3\LocalEventService(
            $app['event_jsonld_repository'],
            $app['event_repository'],
            $app['event_relations_repository'],
            $app['event_iri_generator']
        );

        return $service;
    }
);

$app['personal_variation_decorated_event_service'] = $app->share(
    function (Application $app) {
        $decoratedService = $app['event_service'];

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

$app['event_jsonld_projector'] = $app->share(
    function ($app) {
        $projector = new \CultuurNet\UDB3\Event\ReadModel\JSONLD\EventLDProjector(
            $app['event_jsonld_repository'],
            $app['event_iri_generator'],
            $app['event_service'],
            $app['place_service'],
            $app['organizer_service'],
            $app['media_object_serializer'],
            $app['iri_offer_identifier_factory']
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
            $app['event_relations_repository']
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
                'event_relations_projector',
                'place_relations_projector',
                'event_jsonld_projector',
                'event_history_projector',
                'place_jsonld_projector',
                'organizer_jsonld_projector',
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
                'udb2_actor_events_to_udb3_organizer_applier',
                'place_permission.projector'
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

$app['amqp.connection'] = $app->share(
    function (Application $app) {
        $amqpConfig = $host = $app['config']['amqp'];

        $connection = new AMQPStreamConnection(
            $amqpConfig['host'],
            $amqpConfig['port'],
            $amqpConfig['user'],
            $amqpConfig['password'],
            $amqpConfig['vhost']
        );

        return $connection;
    }
);

$app['amqp.publisher'] = $app->share(
    function (Application $app) {
        $connection = $app['amqp.connection'];
        $exchange = $app['config']['amqp']['publish']['udb3']['exchange'];
        $channel = $connection->channel();

        $map =
            \CultuurNet\UDB3\Event\Events\ContentTypes::map() +
            \CultuurNet\UDB3\Place\Events\ContentTypes::map() +
            \CultuurNet\UDB3\Organizer\Events\ContentTypes::map();

        $classes = (new \CultuurNet\BroadwayAMQP\DomainMessage\SpecificationCollection());
        foreach (array_keys($map) as $className) {
            $classes = $classes->with(
                new \CultuurNet\BroadwayAMQP\DomainMessage\PayloadIsInstanceOf($className)
            );
        }

        $specification = new \CultuurNet\BroadwayAMQP\DomainMessage\AnyOf($classes);

        $contentTypeLookup = new \CultuurNet\BroadwayAMQP\ContentTypeLookup($map);

        $publisher = new \CultuurNet\BroadwayAMQP\AMQPPublisher(
            $channel,
            $exchange,
            $specification,
            $contentTypeLookup,
            new \CultuurNet\BroadwayAMQP\Message\EntireDomainMessageBodyFactory()
        );

        return $publisher;
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

$app['events_locator_event_stream_decorator'] = $app->share(
    function (Application $app) {
        return new OfferLocator($app['event_iri_generator']);
    }
);

$app['real_event_repository'] = $app->share(
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

$app['udb2_event_cdbxml_provider'] = $app->share(
    function (Application $app) {
        $uitidConfig = $app['config']['uitid'];
        $baseUrl = $uitidConfig['base_url'] . $uitidConfig['apis']['entry'];

        $userId = new StringLiteral($uitidConfig['impersonation_user_id']);

        return new \CultuurNet\UDB3\UDB2\EventCdbXmlFromEntryAPI(
            $baseUrl,
            $app['uitid_consumer_credentials'],
            $userId,
            // @todo Move the cdbxml version to configuration file. Use the same
            // setting when instantiating the ImprovedEntryApiFactory.
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL'
        );
    }
);

$app['udb2_event_cdbxml'] = $app->share(
    function (Application $app) {
        $labeledAsUDB3Place = new \CultuurNet\UDB3\UDB2\LabeledAsUDB3Place();

        return new \CultuurNet\UDB3\UDB2\Event\SpecificationDecoratedEventCdbXml(
            $app['udb2_event_cdbxml_provider'],
            new \CultuurNet\UDB3\Cdb\Event\Not($labeledAsUDB3Place)
        );
    }
);

$app['udb2_place_event_cdbxml'] = $app->share(
    function (Application $app) {
        $labeledAsUDB3Place = new \CultuurNet\UDB3\UDB2\LabeledAsUDB3Place();

        return new \CultuurNet\UDB3\UDB2\Event\SpecificationDecoratedEventCdbXml(
            $app['udb2_event_cdbxml_provider'],
            $labeledAsUDB3Place
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

        $commandBus->subscribe($app['bulk_label_offer_command_handler']);

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

$app['place_jsonld_projector'] = $app->share(
    function ($app) {
        $projector = new \CultuurNet\UDB3\Place\ReadModel\JSONLD\PlaceLDProjector(
            $app['place_jsonld_repository'],
            $app['place_iri_generator'],
            $app['organizer_service'],
            $app['media_object_serializer']
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

$app['udb2_log_handler'] = $app->share(
    function (Application $app) {
        return new \Monolog\Handler\StreamHandler(__DIR__ . '/log/udb2.log');
    }
);

$app['udb2_actor_cdbxml_provider'] = $app->share(
    function (Application $app) {
        $cdbXmlService = new \CultuurNet\UDB3\UDB2\ActorCdbXmlFromSearchService(
            $app['search_api_2'],
            CultureFeed_Cdb_Xml::namespaceUriForVersion('3.3')
        );

        return $cdbXmlService;
    }
);

$app['udb2_place_importer'] = $app->share(
    function (Application $app) {
        $importer = new \CultuurNet\UDB3\UDB2\Place\PlaceCdbXmlImporter(
            $app['real_place_repository'],
            $app['udb2_actor_cdbxml_provider'],
            $app['udb2_place_event_cdbxml']
        );

        $logger = new \Monolog\Logger('udb2-place-importer');
        $logger->pushHandler($app['udb2_log_handler']);

        $importer->setLogger($logger);

        return $importer;
    }
);

$app['places_locator_event_stream_decorator'] = $app->share(
    function (Application $app) {
        return new OfferLocator($app['place_iri_generator']);
    }
);

$app['real_place_repository'] = $app->share(
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

$app['organizers_locator_event_stream_decorator'] = $app->share(
    function (Application $app) {
        return new OfferLocator($app['organizer_iri_generator']);
    }
);

$app['real_organizer_repository'] = $app->share(
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

$app['udb2_deserializer_locator'] = $app->share(
    function (Application $app) {
        $deserializerLocator = new SimpleDeserializerLocator();
        $deserializerLocator->registerDeserializer(
            new StringLiteral(
                'application/vnd.cultuurnet.udb2-events.actor-created+json'
            ),
            new \CultuurNet\UDB2DomainEvents\ActorCreatedJSONDeserializer()
        );
        $deserializerLocator->registerDeserializer(
            new StringLiteral(
                'application/vnd.cultuurnet.udb2-events.actor-updated+json'
            ),
            new \CultuurNet\UDB2DomainEvents\ActorUpdatedJSONDeserializer()
        );
        $deserializerLocator->registerDeserializer(
            new StringLiteral(
                'application/vnd.cultuurnet.udb2-events.event-created+json'
            ),
            new \CultuurNet\UDB2DomainEvents\EventCreatedJSONDeserializer()
        );
        $deserializerLocator->registerDeserializer(
            new StringLiteral(
                'application/vnd.cultuurnet.udb2-events.event-updated+json'
            ),
            new \CultuurNet\UDB2DomainEvents\EventUpdatedJSONDeserializer()
        );
        return $deserializerLocator;
    }
);

$app['event_bus_forwarding_consumer_factory'] = $app->share(
    function (Application $app) {
        return new EventBusForwardingConsumerFactory(
            $app['amqp-execution-delay'],
            $app['amqp.connection'],
            $app['logger.amqp.event_bus_forwarder'],
            $app['udb2_deserializer_locator'],
            $app['event_bus'],
            new StringLiteral($app['config']['amqp']['consumer_tag'])
        );
    }
);

$app['amqp.udb2_event_bus_forwarding_consumer'] = $app->share(
    function (Application $app) {
        $consumerConfig = $app['config']['amqp']['consumers']['udb2'];
        $exchange = new StringLiteral($consumerConfig['exchange']);
        $queue = new StringLiteral($consumerConfig['queue']);

        /** @var EventBusForwardingConsumerFactory $consumerFactory */
        $consumerFactory = $app['event_bus_forwarding_consumer_factory'];

        return $consumerFactory->create($exchange, $queue);
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

$app->register(new \CultuurNet\UDB3\Silex\IndexServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\Event\EventEditingServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\Place\PlaceEditingServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\Place\PlaceLookupServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\Organizer\OrganizerLookupServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\User\UserServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\Event\EventPermissionServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\Place\PlacePermissionServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\Offer\OfferServiceProvider());

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

$app->register(new \CultuurNet\UDB3\Silex\Search\SearchServiceProvider());
$app->register(new \CultuurNet\UDB3\Silex\Offer\BulkLabelOfferServiceProvider());

$app->register(
    new \TwoDotsTwice\SilexFeatureToggles\FeatureTogglesProvider(
        isset($app['config']['toggles']) ? $app['config']['toggles'] : []
    )
);

$app->register(
    new \CultuurNet\UDB3\Silex\UDB2IncomingEventServicesProvider(),
    [
        'udb2_cdbxml_enricher.http_response_timeout' => isset($app['config']['udb2_cdbxml_enricher']['http_response_timeout']) ? $app['config']['udb2_cdbxml_enricher']['http_response_timeout'] : 3,
        'udb2_cdbxml_enricher.http_connect_timeout' => isset($app['config']['udb2_cdbxml_enricher']['http_connect_timeout']) ? $app['config']['udb2_cdbxml_enricher']['http_connect_timeout'] : 1,
    ]
);

return $app;

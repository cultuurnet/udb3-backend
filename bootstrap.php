<?php

require 'vendor/autoload.php';

use Silex\Application;
use CultuurNet\UDB3\SearchAPI2\DefaultSearchService as SearchAPI2;
use DerAlex\Silex\YamlConfigServiceProvider;
use CultuurNet\UDB3\Search\PullParsingSearchService;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use JDesrosiers\Silex\Provider\CorsServiceProvider;
use ValueObjects\String\String;

$app = new Application();

$app['debug'] = true;

$app->register(new YamlConfigServiceProvider(__DIR__ . '/config.yml'));

$app->register(new Silex\Provider\UrlGeneratorServiceProvider());
$app->register(new Silex\Provider\SessionServiceProvider());

$app->register(new CorsServiceProvider(), array(
    "cors.allowOrigin" => implode(" ", $app['config']['cors']['origins']),
    "cors.allowCredentials" => true
));


$app->register(new Silex\Provider\SwiftmailerServiceProvider());
$app['swiftmailer.use_spool'] = false;
if ($app['config']['swiftmailer.options']) {
    $app['swiftmailer.options'] = $app['config']['swiftmailer.options'];
}

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

$app['search_service'] = $app->share(
    function ($app) {
        return new PullParsingSearchService(
            $app['search_api_2'],
            $app['iri_generator']
        );
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

$app['current_user'] = $app->share(
    function ($app) {
        /** @var \Symfony\Component\HttpFoundation\Session\SessionInterface $session */
        $session = $app['session'];
        $config = $app['config']['uitid'];

        /** @var \CultuurNet\Auth\User $minimalUserData */
        $minimalUserData = $session->get('culturefeed_user');

        if ($minimalUserData) {
            /** @var \CultuurNet\Auth\ConsumerCredentials $consumerCredentials */
            $consumerCredentials = $app['uitid_consumer_credentials'];
            $userCredentials = $minimalUserData->getTokenCredentials();

            $oauthClient = new CultureFeed_DefaultOAuthClient(
                $consumerCredentials->getKey(),
                $consumerCredentials->getSecret(),
                $userCredentials->getToken(),
                $userCredentials->getSecret()
            );
            $oauthClient->setEndpoint($config['base_url']);

            $cf = new CultureFeed($oauthClient);

            try {
                $private = true;
                $user = $cf->getUser($minimalUserData->getId(), $private);
            } catch (\Exception $e) {
                return NULL;
            }

            unset($user->following);

            return $user;
        }

        return NULL;
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

$app['cache'] = $app->share(
    function ($app) {
        $baseUrl = $app['config']['uitid']['base_url'];
        $baseCacheDirectory = __DIR__ . '/cache';

        return function ($cacheType) use ($baseCacheDirectory) {
            $cacheDirectory = $baseCacheDirectory . '/' . $cacheType;

            $cache = new \Doctrine\Common\Cache\FilesystemCache($cacheDirectory);

            return $cache;
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
        return new \CultuurNet\UDB3\Doctrine\Event\ReadModel\CacheDocumentRepository(
            $app['event_jsonld_cache']
        );
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

$app['event_bus'] = $app->share(
    function ($app) {
        $eventBus = new \CultuurNet\UDB3\SimpleEventBus();

        $eventBus->beforeFirstPublication(function (\Broadway\EventHandling\EventBusInterface $eventBus) use ($app) {
            // Subscribe projector for event relations read model as the first one.
            $eventBus->subscribe(
                $app['relations_projector']
            );

            // Subscribe projector for the JSON-LD read model.
            $eventBus->subscribe(
                $app['event_jsonld_projector']
            );

            // Subscribe event importer which will listen for event creates and
            // updates coming from UDB2 and create/update the corresponding
            // event in our repository as well.
            $eventBus->subscribe(
                $app['udb2_event_importer']
            );

            // Subscribe event history projector.
            $eventBus->subscribe(
                $app['event_history_projector']
            );

            // Subscribe Place JSON-LD projector.
            $eventBus->subscribe(
                $app['place_jsonld_projector']
            );

            // Subscribe Organizer JSON-LD projector.
            $eventBus->subscribe(
                $app['organizer_jsonld_projector']
            );
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

$app['udb2_event_importer'] = $app->share(
    function (Application $app) {
        $cdbXmlService = new \CultuurNet\UDB3\UDB2\EventCdbXmlFromSearchService(
            $app['search_api_2']
        );

        $importer = new \CultuurNet\UDB3\UDB2\EventImporter(
            $cdbXmlService,
            $app['real_event_repository'],
            $app['place_service'],
            $app['organizer_service']
        );

        $logger = new \Monolog\Logger('udb2');
        $logger->pushHandler(
            new \Monolog\Handler\StreamHandler(__DIR__ . '/log/udb2.log')
        );

        $importer->setLogger(
            $logger
        );

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

$app['event_command_bus'] = $app->share(
    function ($app) {
        $mainCommandBus = new \CultuurNet\UDB3\CommandHandling\SimpleContextAwareCommandBus(
        );
        $commandBus = new \CultuurNet\UDB3\CommandHandling\ResqueCommandBus(
            $mainCommandBus,
            'event',
            $app['command_bus_event_dispatcher']
        );
        $commandBus->setLogger($app['logger.command_bus']);
        $commandBus->subscribe(
            new \CultuurNet\UDB3\Event\EventCommandHandler(
                $app['event_repository'],
                $app['search_service']
            )
        );
        $commandBus->subscribe(
            new \CultuurNet\UDB3\EventExport\EventExportCommandHandler(
                $app['event_export'],
                $app['config']['prince']['binary'],
                new \CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\EventInfo\CultureFeedEventInfoService(
                    $app['uitpas']
                )
            )
        );
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
            $app['event_bus']
        );

        return $projector;
    }
);

$app['place_jsonld_repository'] = $app->share(
    function ($app) {
        return new \CultuurNet\UDB3\Doctrine\Event\ReadModel\CacheDocumentRepository(
            $app['place_jsonld_cache']
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

$app['place_repository'] = $app->share(
    function ($app) {
        $repository = new \CultuurNet\UDB3\Place\PlaceRepository(
            $app['place_store'],
            $app['event_bus'],
            array($app['event_stream_metadata_enricher'])
        );

        $udb2RepositoryDecorator = new \CultuurNet\UDB3\UDB2\PlaceRepository(
            $repository,
            $app['search_api_2'],
            $app['udb2_entry_api_improved_factory'],
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

$app['organizer_repository'] = $app->share(
    function ($app) {
        $repository = new \CultuurNet\UDB3\Organizer\OrganizerRepository(
            $app['organizer_store'],
            $app['event_bus'],
            array($app['event_stream_metadata_enricher'])
        );


        $udb2RepositoryDecorator = new \CultuurNet\UDB3\UDB2\OrganizerRepository(
            $repository,
            $app['search_api_2'],
            $app['udb2_entry_api_improved_factory'],
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
            $app['event_service'],
            $app['search_service'],
            new \Broadway\UuidGenerator\Rfc4122\Version4Generator(),
            realpath(__DIR__ .  '/web/downloads'),
            new CallableIriGenerator(
                function ($fileName) use ($app) {
                    return $app['config']['url'] . '/web/downloads/' . $fileName;
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

        $eventBusForwardingConsumer = new \CultuurNet\UDB3\UDB2\AMQP\EventBusForwardingConsumer(
            $connection,
            $app['event_bus'],
            $deserializerLocator
        );

        $logger = new Monolog\Logger('amqp.event_bus_forwarder');
        $logger->pushHandler(new \Monolog\Handler\StreamHandler('php://stdout'));
        $eventBusForwardingConsumer->setLogger($logger);

        return $connection;
    }
);

$app['culturefeed'] = $app->share(
    function (Application $app) {
        $uitidConfig = $app['config']['uitid'];
        $baseUrl = $uitidConfig['base_url'];

        /** @var \CultuurNet\Auth\ConsumerCredentials $consumerCredentials */
        $consumerCredentials = $app['uitid_consumer_credentials'];

        $oauthClient = new \CultureFeed_DefaultOAuthClient(
            $consumerCredentials->getKey(),
            $consumerCredentials->getSecret()
        );
        $oauthClient->setEndpoint($baseUrl);

        $cultureFeed = new \CultureFeed($oauthClient);

        return $cultureFeed;
    }
);

$app['uitpas'] = $app->share(
    function (Application $app) {
        /** @var CultureFeed $culturefeed */
        $cultureFeed = $app['culturefeed'];
        return $cultureFeed->uitpas();
    }
);

return $app;

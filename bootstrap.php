<?php

require 'vendor/autoload.php';

use Silex\Application;
use CultuurNet\UDB3\SearchAPI2\DefaultSearchService as SearchAPI2;
use DerAlex\Silex\YamlConfigServiceProvider;
use CultuurNet\UDB3\Search\PullParsingSearchService;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use JDesrosiers\Silex\Provider\CorsServiceProvider;

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
            $app['eventld_repository'],
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
        $urlParts = parse_url($baseUrl);
        $cacheDirectory = __DIR__ . '/cache/' . $urlParts['host'];
        $cache = new \Doctrine\Common\Cache\FilesystemCache($cacheDirectory);

        return $cache;
    }
);

$app['dbal_connection'] = $app->share(
    function ($app) {
        $connection = \Doctrine\DBAL\DriverManager::getConnection(
            $app['config']['database']
        );
        return $connection;
    }
);

$app['event_store'] = $app->share(
    function ($app) {
        return new \Broadway\EventStore\DBALEventStore(
            $app['dbal_connection'],
            new \Broadway\Serializer\SimpleInterfaceSerializer(),
            new \Broadway\Serializer\SimpleInterfaceSerializer(),
            'events'
        );
    }
);

$app['eventld_repository'] = $app->share(
    function ($app) {
        return new \CultuurNet\UDB3\Doctrine\Event\ReadModel\CacheDocumentRepository(
            $app['cache']
        );
    }
);

$app['event_jsonld_projector'] = $app->share(
    function ($app) {
        return new \CultuurNet\UDB3\Event\EventLDProjector(
            $app['eventld_repository'],
            $app['iri_generator'],
            $app['event_service'],
            $app['place_service'],
            $app['organizer_service']
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
        });

        return $eventBus;
    }
);

$app['read_models_event_bus'] = $app->share(
    function ($app) {
        $eventBus = new \CultuurNet\UDB3\SimpleEventBus();

        $eventBus->beforeFirstPublication(function (\Broadway\EventHandling\EventBusInterface $eventBus) use ($app) {
            $eventBus->subscribe($app['event_jsonld_projector']);
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

$app['event_repository'] = $app->share(
    function ($app) {
        $repository = new \CultuurNet\UDB3\Event\EventRepository(
            $app['event_store'],
            $app['event_bus'],
            array($app['event_stream_metadata_enricher'])
        );


        $udb2RepositoryDecorator = new \CultuurNet\UDB3\UDB2\EventRepository(
            $repository,
            $app['search_api_2'],
            $app['udb2_entry_api_improved_factory'],
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
                $app['event_export']
            )
        );
        return $commandBus;
    }
);

$app['used_keywords_event_bus'] = $app->share(
    function ($app) {
        $eventBus = new \Broadway\EventHandling\SimpleEventBus();
        return $eventBus;
    }
);

$app['used_keywords_memory'] = $app->share(
    function ($app) {
        return new \CultuurNet\UDB3\UsedKeywordsMemory\DefaultUsedKeywordsMemoryService(
            new \CultuurNet\UDB3\UsedKeywordsMemory\UsedKeywordsMemoryRepository(
                $app['event_store'],
                $app['used_keywords_event_bus']
            )
        );
    }
);

$app['event_tagger'] = $app->share(
    function ($app) {
        return new \CultuurNet\UDB3\Event\DefaultEventTaggerService(
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
            $app['eventld_repository'],
            $app['place_iri_generator'],
            $app['read_models_event_bus']
        );

        return $projector;
    }
);

$app['place_event_bus'] = $app->share(
    function ($app) {
        $eventBus = new \CultuurNet\UDB3\SimpleEventBus();

        $eventBus->beforeFirstPublication(function (\Broadway\EventHandling\EventBusInterface $eventBus) use ($app) {
            $eventBus->subscribe(
                $app['place_jsonld_projector']
            );
        });

        return $eventBus;
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
            new \Broadway\Serializer\SimpleInterfaceSerializer(),
            new \Broadway\Serializer\SimpleInterfaceSerializer(),
            'places'
        );
    }
);

$app['place_repository'] = $app->share(
    function ($app) {
        $repository = new \CultuurNet\UDB3\Place\PlaceRepository(
            $app['place_store'],
            $app['place_event_bus'],
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
            $app['eventld_repository'],
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
          $app['eventld_repository'],
          $app['organizer_iri_generator'],
          $app['organizer_event_bus']
      );
  }
);

$app['organizer_event_bus'] = $app->share(
    function ($app) {
        $eventBus = new \CultuurNet\UDB3\SimpleEventBus();
        $eventBus->beforeFirstPublication(function (\Broadway\EventHandling\EventBusInterface $eventBus) use ($app) {
           $eventBus->subscribe(
               $app['organizer_jsonld_projector']
           );
        });

        return $eventBus;
    }
);

$app['organizer_store'] = $app->share(
    function ($app) {
        return new \Broadway\EventStore\DBALEventStore(
            $app['dbal_connection'],
            new \Broadway\Serializer\SimpleInterfaceSerializer(),
            new \Broadway\Serializer\SimpleInterfaceSerializer(),
            'organizers'
        );
    }
);

$app['organizer_repository'] = $app->share(
    function ($app) {
        $repository = new \CultuurNet\UDB3\Organizer\OrganizerRepository(
            $app['organizer_store'],
            $app['organizer_event_bus'],
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
            $app['eventld_repository'],
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

return $app;

<?php

require 'vendor/autoload.php';

use Silex\Application;
use CultuurNet\UDB3\Doctrine\EventServiceCache;
use CultuurNet\UDB3\SearchAPI2\DefaultSearchService as SearchAPI2;
use DerAlex\Silex\YamlConfigServiceProvider;
use CultuurNet\UDB3\Search\PullParsingSearchService;
use CultuurNet\UDB3\DefaultEventService;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use JDesrosiers\Silex\Provider\CorsServiceProvider;

$app = new Application();

$app['debug'] = true;

$app->register(new YamlConfigServiceProvider(__DIR__ . '/config.yml'));

$app->register(new Silex\Provider\UrlGeneratorServiceProvider());
$app->register(new Silex\Provider\SessionServiceProvider());

$app->register(new CorsServiceProvider(), array(
    "cors.allowOrigin" => implode (", ", $app['config']['cors']['origins']),
    "cors.allowCredentials" => true
));


$app['iri_generator'] = $app->share(
    function ($app) {
        return new CallableIriGenerator(
            function ($cdbid) use ($app) {
                /** @var \Symfony\Component\Routing\Generator\UrlGeneratorInterface $urlGenerator */
                $urlGenerator = $app['url_generator'];

                return $urlGenerator->generate(
                    'event',
                    array(
                        'cdbid' => $cdbid,
                    ),
                    $urlGenerator::ABSOLUTE_URL
                );
            }
        );
    }
);

$app['search_api_2'] = $app->share(
    function ($app) {
        $searchConfig = $app['config']['search'];
        $consumerCredentials = new \CultuurNet\Auth\ConsumerCredentials();
        $consumerCredentials->setKey($searchConfig['consumer']['key']);
        $consumerCredentials->setSecret($searchConfig['consumer']['secret']);
        return new SearchAPI2($searchConfig['base_url'], $consumerCredentials);
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
        $service = new DefaultEventService(
            $app['search_api_2'],
            $app['iri_generator']
        );
        return new EventServiceCache($service, $app['cache']);
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
            $userCredentials = $minimalUserData->getTokenCredentials();

            $oauthClient = new CultureFeed_DefaultOAuthClient(
                $config['consumer']['key'],
                $config['consumer']['secret'],
                $userCredentials->getToken(),
                $userCredentials->getSecret()
            );
            $oauthClient->setEndpoint($config['base_url']);

            $cf = new CultureFeed($oauthClient);

            $user = $cf->getUser($minimalUserData->getId());

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
            new \CultuurNet\Auth\ConsumerCredentials(
                $uitidConfig['consumer']['key'],
                $uitidConfig['consumer']['secret']
            )
        );
    }
);

$app['cache'] = $app->share(
    function ($app) {
        $cacheDirectory = __DIR__ . '/cache';
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

$app['event_bus'] = $app->share(
    function ($app) {
        return new \Broadway\EventHandling\SimpleEventBus();
    }
);

$app['execution_context_metadata_enricher'] = $app->share(
    function ($app) {
        return new \CultuurNet\UDB3\EventSourcing\ExecutionContextMetadataEnricher();
    }
);

$app['event_stream_metadata_enricher'] = $app->share(
    function ($app) {
        $eventStreamDecorator = new \Broadway\EventSourcing\MetadataEnrichment\MetadataEnrichingEventStreamDecorator();
        $eventStreamDecorator->registerEnricher(
            $app['execution_context_metadata_enricher']
        );
        return $eventStreamDecorator;
    }
);

$app['event_repository'] = $app->share(
    function ($app) {
        return new \CultuurNet\UDB3\Event\EventRepository(
            $app['event_store'],
            $app['event_bus'],
            $app['search_api_2'],
            array($app['event_stream_metadata_enricher'])
        );
    }
);

$app['command_bus_event_dispatcher'] = $app->share(
  function ($app) {
      $dispatcher = new \Broadway\EventDispatcher\EventDispatcher();
      $dispatcher->addListener(
          \CultuurNet\UDB3\CommandHandling\ResqueCommandBus::EVENT_COMMAND_CONTEXT_SET,
          function ($context) use ($app) {
              $app['execution_context_metadata_enricher']->setContext($context);
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
        $mainCommandBus = new \CultuurNet\UDB3\CommandHandling\SimpleContextAwareCommandBus();
        $commandBus = new \CultuurNet\UDB3\CommandHandling\ResqueCommandBus(
            $mainCommandBus,
            'event',
            $app['command_bus_event_dispatcher']
        );
        $commandBus->setLogger($app['logger.command_bus']);
        $commandBus->subscribe(
            new \CultuurNet\UDB3\Event\EventCommandHandler(
                $app['event_repository']
            )
        );
        return $commandBus;
    }
);

$app['used_keywords_memory'] = $app->share(
    function ($app) {
        return new \CultuurNet\UDB3\UsedKeywordsMemory\DefaultUsedKeywordsMemoryService(
            new \CultuurNet\UDB3\UsedKeywordsMemory\UsedKeywordsMemoryRepository(
                $app['event_store'],
                $app['event_bus']
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

return $app;

<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use CultuurNet\UDB3\SearchAPI2\DefaultSearchService as SearchAPI2;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use CultuurNet\UDB3\Symfony\JsonLdResponse;
use CultuurNet\UDB3\Event\EventTaggerServiceInterface;

/** @var Application $app */
$app = require __DIR__ . '/../bootstrap.php';

$checkAuthenticated = function (Request $request, Application $app) {
    /** @var \Symfony\Component\HttpFoundation\Session\SessionInterface $session */
    $session = $app['session'];

    if (!$session->get('culturefeed_user')) {
        return new Response('Access denied', 403);
    }
};

$app['logger.search'] = $app->share(
    function ($app) {
        $logger = new \Monolog\Logger('search');

        $handlers = $app['config']['log.search'];
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
                        __DIR__ . '/' . $handler_config['path']
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

// Enable CORS.
$app->after($app["cors"]);
//$app->after(
//    function (Request $request, Response $response, Application $app) {
//        $origin = $request->headers->get('Origin');
//        $origins = $app['config']['cors']['origins'];
//        if (!empty($origins) && in_array($origin, $origins)) {
//            $response->headers->set(
//                'Access-Control-Allow-Origin',
//                $origin
//            );
//            $response->headers->set(
//                'Access-Control-Allow-Credentials',
//                'true'
//            );
//        }
//    }
//);

$app->before(
    function (Request $request) {
        if (0 === strpos(
                $request->headers->get('Content-Type'),
                'application/json'
            )
        ) {
            $data = json_decode($request->getContent(), true);
            $request->request->replace(is_array($data) ? $data : array());
        }
    }
);

// Set execution context for the asynchronous command bus.
// @todo Limit this to the paths where the command bus is used.
$app->before(
    function (Request $request, Application $app) {
        /** @var \Broadway\CommandHandling\CommandBusInterface|\CultuurNet\UDB3\CommandHandling\ContextAwareInterface $eventCommandBus */
        $eventCommandBus = $app['event_command_bus'];

        /** @var CultureFeed_User $user */
        $user = $app['current_user'];

        $contextValues = array();
        if ($user) {
            $contextValues['user_id'] = $user->id;
            $contextValues['user_nick'] = $user->nick;
        }
        $contextValues['client_ip'] = $request->getClientIp();
        $contextValues['request_time'] = $_SERVER['REQUEST_TIME'];
        $context = new \Broadway\Domain\Metadata($contextValues);
        $eventCommandBus->setContext($context);
    }
);

$app->get(
    'culturefeed/oauth/connect',
    function (Request $request, Application $app) {
        /** @var CultuurNet\Auth\ServiceInterface $authService */
        $authService = $app['auth_service'];

        /** @var \Symfony\Component\Routing\Generator\UrlGeneratorInterface $urlGenerator */
        $urlGenerator = $app['url_generator'];

        $callback_url_params = array();

        if ($request->query->get('destination')) {
            $callback_url_params['destination'] = $request->query->get(
                'destination'
            );
        }

        $callback_url = $urlGenerator->generate(
            'culturefeed.oauth.authorize',
            $callback_url_params,
            $urlGenerator::ABSOLUTE_URL
        );

        $token = $authService->getRequestToken($callback_url);

        $authorizeUrl = $authService->getAuthorizeUrl($token);

        /** @var \Symfony\Component\HttpFoundation\Session\Session $session */
        $session = $app['session'];
        $session->set('culturefeed_tmp_token', $token);

        return new RedirectResponse($authorizeUrl);
    }
);

$app->get(
    'culturefeed/oauth/authorize',
    function (Request $request, Application $app) {
        /** @var \Symfony\Component\HttpFoundation\Session\Session $session */
        $session = $app['session'];

        /** @var CultuurNet\Auth\ServiceInterface $authService */
        $authService = $app['auth_service'];

        /** @var \Symfony\Component\Routing\Generator\UrlGeneratorInterface $urlGenerator */
        $urlGenerator = $app['url_generator'];
        $query = $request->query;

        /** @var \CultuurNet\Auth\TokenCredentials $token */
        $token = $session->get('culturefeed_tmp_token');

        if ($query->get('oauth_token') == $token->getToken() && $query->get(
                'oauth_verifier'
            )
        ) {

            $user = $authService->getAccessToken(
                $token,
                $query->get('oauth_verifier')
            );

            $session->remove('culturefeed_tmp_token');

            $session->set('culturefeed_user', $user);
        }

        if ($query->get('destination')) {
            return new RedirectResponse(
                $query->get('destination')
            );
        } else {
            return new RedirectResponse(
                $urlGenerator->generate('api/1.0/search')
            );
        }
    }
)
    ->bind('culturefeed.oauth.authorize');

$app->get(
    'logout',
    function (Request $request, Application $app) {
        /** @var \Symfony\Component\HttpFoundation\Session\Session $session */
        $session = $app['session'];
        $session->invalidate();

        return new Response('Logged out');
    }
);

$app->get(
    'search',
    function (Request $request, Application $app) {
        $q = $request->query->get('q');
        $limit = new \CultuurNet\Search\Parameter\Rows(
            $request->query->get('limit', 30)
        );
        $start = new \CultuurNet\Search\Parameter\Start(
            $request->query->get('start', 0)
        );
        $group = new \CultuurNet\Search\Parameter\Group();
        $typeFilter = new \CultuurNet\Search\Parameter\FilterQuery(
            'type:event'
        );


        /** @var SearchAPI2 $service */
        $service = $app['search_api_2'];
        $q = new \CultuurNet\Search\Parameter\Query($q);
        $response = $service->search(
            array($q, $limit, $start, $group, $typeFilter)
        );

        $results = \CultuurNet\Search\SearchResult::fromXml(
            new SimpleXMLElement(
                $response->getBody(true),
                0,
                false,
                \CultureFeed_Cdb_Default::CDB_SCHEME_URL
            )
        );

        $response = Response::create()
            ->setContent($results->getXml())
            ->setPublic()
            ->setClientTtl(60 * 1)
            ->setTtl(60 * 5);

        $response->headers->set('Content-Type', 'text/xml');

        return $response;
    }
)->before($checkAuthenticated);

$app->get(
    'api/1.0/event.jsonld',
    function (Request $request, Application $app) {
        $response = new \Symfony\Component\HttpFoundation\BinaryFileResponse(
            'api/1.0/event.jsonld'
        );
        $response->headers->set('Content-Type', 'application/ld+json');
        return $response;
    }
);

$app->get(
    'db_init',
    function (Request $request, Application $app) {
        /** @var \Broadway\EventStore\DBALEventStore $eventStore */
        $eventStore = $app['event_store'];

        /** @var \Doctrine\DBAL\Connection $connection */
        $connection = $app['dbal_connection'];

        $schemaManager = $connection->getSchemaManager();
        $schema = $schemaManager->createSchema();

        $table = $eventStore->configureSchema($schema);
        $schemaManager->createTable($table);
    }
);

$app->get(
    'api/1.0/search',
    function (Request $request, Application $app) {
        $query = $request->query->get('query', '*.*');
        $limit = $request->query->get('limit', 30);
        $start = $request->query->get('start', 0);

        /** @var Psr\Log\LoggerInterface $logger */
        $logger = $app['logger.search'];
        /** @var CultureFeed_User $user */
        $user = $app['current_user'];

        /** @var \CultuurNet\UDB3\Search\SearchServiceInterface $searchService */
        $searchService = $app['search_service'];
        try {
            $results = $searchService->search($query, $limit, $start);
            $logger->info(
                "Search for: {$query}",
                array('user' => $user->nick)
            );
        } catch (\Guzzle\Http\Exception\ClientErrorResponseException $e) {

            $logger->alert(
                "Search failed with HTTP status {$e->getResponse(
                )->getStatusCode()}. Query: {$query}",
                array('user' => $user->nick)
            );

            return new Response('Error while searching', '400');
        }

        $response = JsonLdResponse::create()
            ->setData($results)
            ->setPublic()
            ->setClientTtl(60 * 1)
            ->setTtl(60 * 5);

        return $response;
    }
)->before($checkAuthenticated)->bind('api/1.0/search');

$app
    ->get(
        'event/{cdbid}',
        function (Request $request, Application $app, $cdbid) {
            /** @var \CultuurNet\UDB3\EventServiceInterface $service */
            $service = $app['event_service'];

            $event = $service->getEvent($cdbid);

            $response = JsonLdResponse::create()
                ->setData($event)
                ->setPublic()
                ->setClientTtl(60 * 30)
                ->setTtl(60 * 5);

            return $response;
        }
    )
    ->bind('event');

$app->get(
    'api/1.0/user',
    function (Request $request, Application $app) {
        /** @var CultureFeed_User $user */
        $user = $app['current_user'];

        $response = JsonLdResponse::create()
            ->setData($user)
            ->setPrivate();

        return $response;
    }
)->before($checkAuthenticated);

$app->get(
    'api/1.0/user/keywords',
    function (Request $request, Application $app) {
        /** @var \CultuurNet\UDB3\UsedKeywordsMemory\UsedKeywordsMemoryServiceInterface $usedKeywordsMemoryService */
        $usedKeywordsMemoryService = $app['used_keywords_memory'];
        $user = $app['current_user'];
        $memory = $usedKeywordsMemoryService->getMemory($user->id);

        return JsonResponse::create($memory);
    }
)->before($checkAuthenticated);

$app->post(
    'events/tag',
    function (Request $request, Application $app) {
        /** @var EventTaggerServiceInterface $eventTagger */
        $eventTagger = $app['event_tagger'];

        $keyword = new \CultuurNet\UDB3\Keyword($request->request->get('keyword'));
        $eventIds = $request->request->get('events');

        $response = new JsonResponse();

        try {
            $commandId = $eventTagger->tagEventsById($eventIds, $keyword);

            /** @var CultureFeed_User $user */
            $user = $app['current_user'];
            $app['used_keywords_memory']->rememberKeywordUsed(
                $user->id,
                $keyword
            );

            $response->setData(['commandId' => $commandId]);

        } catch (Exception $e) {
            $response->setStatusCode(400);
            $response->setData(['error' => $e->getMessage()]);
        };

        return $response;
    }
)->before($checkAuthenticated);

$app->get(
    'command/{token}',
    function (Request $request, Application $app, $token) {
        $status = new Resque_Job_Status($token);

        $code = $status->get();

        if (false === $code) {
            // @todo 404 not found response
        }

        $labels = array(
            Resque_Job_Status::STATUS_WAITING => 'waiting',
            Resque_Job_Status::STATUS_RUNNING => 'running',
            Resque_Job_Status::STATUS_COMPLETE => 'complete',
            Resque_Job_Status::STATUS_FAILED => 'failed'
        );

        return new Response($labels[$code]);
    }
);

$app->run();

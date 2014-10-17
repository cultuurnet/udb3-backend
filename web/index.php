<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use CultuurNet\UDB3\SearchAPI2\DefaultSearchService as SearchAPI2;
use DerAlex\Silex\YamlConfigServiceProvider;
use CultuurNet\UDB3\PullParsingSearchService;
use CultuurNet\UDB3\DefaultEventService;

$app = new Application();

$app['debug'] = true;

$app->register(new YamlConfigServiceProvider(__DIR__ . '/../config.yml'));

// Enable CORS.
$app->after(
    function (Request $request, Response $response, Application $app) {
        $origin = $request->headers->get('Origin');
        $origins = $app['config']['cors']['origins'];
        if (!empty($origins) && in_array($origin, $origins)) {
            $response->headers->set(
                'Access-Control-Allow-Origin',
                $origin
            );
        }
    }
);

$app['search_api_2'] = $app->share(
    function($app) {
        $searchConfig = $app['config']['search'];
        $consumerCredentials = new \CultuurNet\Auth\ConsumerCredentials();
        $consumerCredentials->setKey($searchConfig['consumer']['key']);
        $consumerCredentials->setSecret($searchConfig['consumer']['secret']);
        return new SearchAPI2($searchConfig['base_url'], $consumerCredentials);
    }
);

$app['search_service'] = $app->share(
    function($app) {
        return new PullParsingSearchService($app['search_api_2']);
    }
);

$app['event_service'] = $app->share(
    function($app) {
        return new DefaultEventService($app['search_api_2']);
    }
);


$app->get(
    'search',
    function (Request $request, Application $app) {
        $q = $request->query->get('q');

        /** @var SearchAPI2 $service */
        $service = $app['search_api_2'];
        $q = new \CultuurNet\Search\Parameter\Query($q);
        $response = $service->search(array($q));

        $results = \CultuurNet\Search\SearchResult::fromXml(new SimpleXMLElement($response->getBody(true), 0, false, \CultureFeed_Cdb_Default::CDB_SCHEME_URL));

        $response = Response::create()
            ->setContent($results->getXml())
            ->setPublic()
            ->setClientTtl(60 * 1)
            ->setTtl(60 * 5);

        $response->headers->set('Content-Type', 'text/xml');

        return $response;
    }
);

$app->get(
    'api/1.0/event.jsonld',
    function(Request $request, Application $app) {
        $response = new \Symfony\Component\HttpFoundation\BinaryFileResponse('api/1.0/event.jsonld');
        $response->headers->set('Content-Type', 'application/ld+json');
        return $response;
    }
);

$app->get(
    'api/1.0/search',
    function (Request $request, Application $app) {
        $query = $request->query->get('query', '*.*');
        $limit = $request->query->get('limit', 30);
        $start = $request->query->get('start', 0);

        /** @var \CultuurNet\UDB3\SearchServiceInterface $searchService */
        $searchService = $app['search_service'];
        $results = $searchService->search($query, $limit, $start);

        $response = Response::create()
            ->setContent(json_encode($results))
            ->setPublic()
            ->setClientTtl(60 * 1)
            ->setTtl(60 * 5);

        $response->headers->set('Content-Type', 'application/ld+json');

        return $response;
    }
);

$app->get(
    'event/{cdbid}',
    function(Request $request, Application $app, $cdbid) {
        /** @var \CultuurNet\UDB3\EventServiceInterface $service */
        $service = $app['event_service'];

        /** @var \Symfony\Component\HttpFoundation\JsonResponse $response */
        $response = \Symfony\Component\HttpFoundation\JsonResponse::create()
            ->setPublic()
            ->setClientTtl(60 * 1)
            ->setTtl(60 * 5);

        $event = $service->getEvent($cdbid);
        $response
            ->setData($event)
            ->setPublic()
            ->setClientTtl(60 * 30)
            ->setTtl(60 * 5);

        $response->headers->set('Content-Type', 'application/ld+json');

        return $response;
    }
);

$app->run();

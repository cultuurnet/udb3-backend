<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use CultuurNet\UDB3\SearchAPI2\DefaultSearchService as SearchAPI2;
use DerAlex\Silex\YamlConfigServiceProvider;
use CultuurNet\UDB3\DefaultSearchService;
use CultuurNet\UDB3\EventService;

$app = new Application();

$app['debug'] = true;

$app->register(new YamlConfigServiceProvider(__DIR__ . '/../config.yml'));

// Enable CORS.
$app->after(
    function (Request $request, Response $response, Application $app) {
        $origins = $app['config']['cors']['origins'];
        if (!empty($origins)) {
            $response->headers->set(
                'Access-Control-Allow-Origin',
                implode(' ', $origins)
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
        return new DefaultSearchService($app['search_api_2']);
    }
);

$app['event_service'] = $app->share(
    function($app) {
        return new EventService($app['search_service']);
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
        $response->headers->set('Access-Control-Allow-Origin', 'http://localhost:9000');

        return $response;
    }
);

$app->get(
    'event/{cdbid}',
    function(Request $request, Application $app, $cdbid) {
        /** @var Service $service */
        $service = $app['search_api_2'];
        $cdbidCondition = 'cdbid:' . $cdbid;
        $results = $service->search(array(
                new \CultuurNet\Search\Parameter\Query($cdbidCondition),
                new \CultuurNet\Search\Parameter\Group(),
        ));

        /** @var \Symfony\Component\HttpFoundation\JsonResponse $response */
        $response = \Symfony\Component\HttpFoundation\JsonResponse::create()
            ->setPublic()
            ->setClientTtl(60 * 1)
            ->setTtl(60 * 5);

        //$response->headers->set('Content-Type', 'text/xml');

        $items = $results->getItems();
        /** @var \CultuurNet\Search\ActivityStatsExtendedEntity $first */
        $first = reset($items);

        // @todo Only return event xml
        $response->setData(array(
                'xml' => $results->getXml(),
            ));

        // @todo convert to json-ld
        //$response->headers->set('Content-Type', 'application/ld+json');

        return $response;
    }
);

$app->run();

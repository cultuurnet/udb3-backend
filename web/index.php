<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use CultuurNet\Search\Guzzle\Service;
use DerAlex\Silex\YamlConfigServiceProvider;

$app = new Application();

$app['debug'] = true;

$app->register(new YamlConfigServiceProvider(__DIR__ . '/../config.yml'));

$app['search_service'] = $app->share(function($app) {
        $searchConfig = $app['config']['search'];
        $consumerCredentials = new \CultuurNet\Auth\ConsumerCredentials();
        $consumerCredentials->setKey($searchConfig['consumer']['key']);
        $consumerCredentials->setSecret($searchConfig['consumer']['secret']);
    return new Service($searchConfig['base_url'], $consumerCredentials);
});

$app->get(
    'search',
    function (Request $request, Application $app) {
        $q = $request->query->get('q');

        /** @var Service $service */
        $service = $app['search_service'];
        $results = $service->search(array(new \CultuurNet\Search\Parameter\Query($q)));

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
    'event/{cdbid}',
    function(Request $request, Application $app, $cdbid) {
        /** @var Service $service */
        $service = $app['search_service'];
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

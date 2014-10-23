<?php

require 'vendor/autoload.php';

use Silex\Application;
use CultuurNet\UDB3\Doctrine\EventServiceCache;
use CultuurNet\UDB3\SearchAPI2\DefaultSearchService as SearchAPI2;
use DerAlex\Silex\YamlConfigServiceProvider;
use CultuurNet\UDB3\PullParsingSearchService;
use CultuurNet\UDB3\DefaultEventService;
use CultuurNet\UDB3\CallableIriGenerator;

$app = new Application();

$app['debug'] = true;

$app->register(new YamlConfigServiceProvider(__DIR__ . '/config.yml'));

$app->register(new Silex\Provider\UrlGeneratorServiceProvider());
$app->register(new Silex\Provider\SessionServiceProvider());


$app['iri_generator'] = $app->share(
    function($app) {
        return new CallableIriGenerator(function ($cdbid) use ($app) {
                /** @var \Symfony\Component\Routing\Generator\UrlGeneratorInterface $urlGenerator */
                $urlGenerator = $app['url_generator'];

                return $urlGenerator->generate(
                    'event',
                    array(
                        'cdbid' => $cdbid,
                    ),
                    $urlGenerator::ABSOLUTE_URL
                );
            });
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
        return new PullParsingSearchService($app['search_api_2'], $app['iri_generator']);
    }
);

$app['event_service'] = $app->share(
    function($app) {
        $service = new DefaultEventService($app['search_api_2'], $app['iri_generator']);
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

        $userCredentials = $minimalUserData->getTokenCredentials();

        $oauthClient = new CultureFeed_DefaultOAuthClient(
            $config['consumer']['key'],
            $config['consumer']['secret'],
            $userCredentials->getToken(),
            $userCredentials->getSecret()
        );
        $oauthClient->setEndpoint($config['base_url']);

        $cf = new CultureFeed($oauthClient);

        return $cf->getUser($minimalUserData->getId());
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

return $app;

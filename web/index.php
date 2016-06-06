<?php

require_once __DIR__ . '/../vendor/autoload.php';

use CultuurNet\Auth\TokenCredentials;
use CultuurNet\SymfonySecurityJwt\Authentication\JwtUserToken;
use CultuurNet\SymfonySecurityOAuth\Security\OAuthToken;
use CultuurNet\UiTIDProvider\Security\MultiPathRequestMatcher;
use CultuurNet\UiTIDProvider\Security\PreflightRequestMatcher;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/** @var Application $app */
$app = require __DIR__ . '/../bootstrap.php';

/**
 * Allow to use services as controllers.
 */
$app->register(new Silex\Provider\ServiceControllerServiceProvider());

/**
 * Firewall configuration.
 *
 * We can not expect the UUID of events, places and organizers
 * to be correctly formatted, because there is no exhaustive documentation
 * about how this is handled in UDB2. Therefore we take a rather liberal
 * approach and allow all alphanumeric characters and a dash.
 */
$app['cors_preflight_request_matcher'] = $app->share(
    function () {
        return new PreflightRequestMatcher();
    }
);

$app['id_pattern'] = '[\w\-]+';
$app['security.firewalls'] = array(
    'authentication' => array(
        'pattern' => '^/culturefeed/oauth',
    ),
    'public' => array(
        'pattern' => new MultiPathRequestMatcher(
            [
                '^/api/1.0/event.jsonld',
                '^/(event|place|label)/' . $app['id_pattern'] . '$',
                '^/event/' . $app['id_pattern'] . '/history',
                '^/organizer/' . $app['id_pattern'],
                '^/media/' . $app['id_pattern'] . '$',
                '^/places$',
                '^/api/1.0/organizer/suggest/.*'
            ],
            [
                'GET',
                'GET',
                'GET',
                'GET',
                'GET',
                'GET',
                'GET'
            ]
        )
    ),
    'entryapi' => array(
        'pattern' => '^/rest/entry/.*',
        'oauth' => true,
        'stateless' => true,
    ),
    'cors-preflight' => array(
        'pattern' => $app['cors_preflight_request_matcher'],
    ),
    'secured' => array(
        'pattern' => '^.*$',
        'jwt' => [
            'validation' => $app['config']['jwt']['validation'],
            'required_claims' => [
                'uid',
                'nick',
                'email',
            ],
            'public_key' => 'file://' . __DIR__ . '/../' . $app['config']['jwt']['keys']['public']['file'],
        ],
        'stateless' => true,
    ),
);

/**
 * Security services.
 */
$app->register(new \Silex\Provider\SecurityServiceProvider());
$app->register(new \CultuurNet\SilexServiceProviderJwt\JwtServiceProvider());

require __DIR__ . '/../debug.php';

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

if (isset($app['config']['cdbxml_proxy']) &&
    $app['config']['cdbxml_proxy']['enabled']) {
    $app->before(
        function (Request $request, Application $app) {
            /** @var \CultuurNet\UDB3\Symfony\Proxy\CdbXmlProxy $cdbXmlProxy */
            $cdbXmlProxy = $app['cdbxml_proxy'];

            return $cdbXmlProxy->handle($request);
        },
        Application::EARLY_EVENT
    );
}

/**
 * Bootstrap metadata based on user context.
 */
$app->before(
    function (Request $request, Application $app) {
        $contextValues = [];

        $contextValues['client_ip'] = $request->getClientIp();
        $contextValues['request_time'] = $_SERVER['REQUEST_TIME'];

        /** @var \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage $tokenStorage */
        $tokenStorage = $app['security.token_storage'];
        $authToken = $tokenStorage->getToken();

        // Web service consumer authenticated with OAuth.
        if ($authToken instanceof OAuthToken &&
            $authToken->isAuthenticated()
        ) {
            $contextValues['uitid_token_credentials'] = new TokenCredentials(
                $authToken->getAccessToken()->getToken(),
                $authToken->getAccessToken()->getSecret()
            );
            $contextValues['consumer'] = [
                'key' => $authToken->getAccessToken()->getConsumer()->getConsumerKey(),
                'secret' => $authToken->getAccessToken()->getConsumer()->getConsumerSecret(),
                'name' => $authToken->getAccessToken()->getConsumer()->getName()
            ];
            $user = $authToken->getUser();

            if ($user instanceof \CultuurNet\SymfonySecurityOAuthUitid\User) {
                $contextValues['user_id'] = $user->getUid();
                $contextValues['user_nick'] = $user->getUsername();
                $contextValues['user_email'] = $user->getEmail();
            }
        } else if ($authToken instanceof JwtUserToken && $authToken->isAuthenticated()) {
            $jwt = $authToken->getCredentials();
            $contextValues['user_id'] = $jwt->getClaim('uid');
            $contextValues['user_nick'] = $jwt->getClaim('nick');
            $contextValues['user_email'] = $jwt->getClaim('email');
        }

        $contextValues['client_ip'] = $request->getClientIp();
        $contextValues['request_time'] = $_SERVER['REQUEST_TIME'];

        /** @var \CultuurNet\UDB3\EventSourcing\ExecutionContextMetadataEnricher $metadataEnricher */
        $metadataEnricher = $app['execution_context_metadata_enricher'];
        $metadataEnricher->setContext(new \Broadway\Domain\Metadata($contextValues));
    }
);

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

$app->mount('events/export', new \CultuurNet\UDB3\Silex\Export\ExportControllerProvider());

$app->get(
    'swagger.json',
    function (Request $request) {
        $file = new SplFileInfo(__DIR__ . '/swagger.json');
        return new \Symfony\Component\HttpFoundation\BinaryFileResponse(
            $file,
            200,
            [
                'Content-Type' => 'application/json',
            ]
        );
    }
);

$app->mount('saved-searches', new \CultuurNet\UDB3\Silex\SavedSearches\SavedSearchesControllerProvider());

$app->mount('variations', new \CultuurNet\UDB3\Silex\Variations\VariationsControllerProvider());

$app->mount('rest/entry', new \CultuurNet\UDB3SilexEntryAPI\EventControllerProvider());

$app->register(new \CultuurNet\UDB3\Silex\ErrorHandlerProvider());
$app->mount('/', new \CultuurNet\UDB3\Silex\Search\SearchControllerProvider());
$app->mount('/', new \CultuurNet\UDB3\Silex\Place\PlaceControllerProvider());
$app->mount('/', new \CultuurNet\UDB3\Silex\Organizer\OrganizerControllerProvider());
$app->mount('/', new \CultuurNet\UDB3\Silex\Event\EventControllerProvider());
$app->mount('/', new \CultuurNet\UDB3\Silex\Media\MediaControllerProvider());
$app->mount('/', new \CultuurNet\UDB3\Silex\Offer\OfferControllerProvider());
$app->mount('/', new \CultuurNet\UDB3\Silex\Offer\BulkLabelOfferControllerProvider());
$app->mount('/', new \CultuurNet\UDB3\Silex\User\UserControllerProvider());
$app->mount('dashboard/', new \CultuurNet\UDB3\Silex\Dashboard\DashboardControllerProvider());
$app->mount('label', new \CultuurNet\UDB3\Silex\Labels\LabelsControllerProvider());

$app->get(
    '/user',
    function (Application $app) {
        return (new JsonResponse())
            ->setData($app['current_user'])
            ->setPrivate();
    }
);

/**
 * Basic REST API for feature toggles.
 */
$app->mount('/', new \TwoDotsTwice\SilexFeatureToggles\FeatureTogglesControllerProvider());

$app->run();

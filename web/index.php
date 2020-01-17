<?php

require_once __DIR__ . '/../vendor/autoload.php';

use CultuurNet\UDB3\HttpFoundation\RequestMatcher\AnyOfRequestMatcher;
use CultuurNet\UDB3\HttpFoundation\RequestMatcher\PreflightRequestMatcher;
use CultuurNet\UDB3\Jwt\Silex\JwtServiceProvider;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Silex\ApiName;
use CultuurNet\UDB3\Silex\FeatureControllerProvider;
use CultuurNet\UDB3\Silex\Import\ImportControllerProvider;
use CultuurNet\UDB3\Silex\Role\UserPermissionsServiceProvider;
use CultuurNet\UDB3\Http\Management\PermissionsVoter;
use CultuurNet\UDB3\Http\Management\UserPermissionsVoter;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManager;

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
    'public' => [
        'pattern' => (new AnyOfRequestMatcher())
            ->with(new RequestMatcher('^/contexts/.*', null, 'GET'))
            ->with(new RequestMatcher('^/(event|place|label)/' . $app['id_pattern'] . '$', null, 'GET'))
            ->with(new RequestMatcher('^/(events|places)/' . $app['id_pattern'] . '$', null, 'GET'))
            ->with(new RequestMatcher('^/(events|places)/' . $app['id_pattern'] . '/calsum$', null, 'GET'))
            ->with(new RequestMatcher('^/(events|places)/' . $app['id_pattern'] . '/permissions/.+$', null, 'GET'))
            /* @deprecated */
            ->with(new RequestMatcher('^/(event|place)/' . $app['id_pattern'] . '/permission/.+$', null, 'GET'))
            ->with(new RequestMatcher('^/organizers/' . $app['id_pattern'] . '$', null, 'GET'))
            ->with(new RequestMatcher('^/organizers/' . $app['id_pattern'] . '/permissions/.+$', null, 'GET'))
            ->with(new RequestMatcher('^/media/' . $app['id_pattern'] . '$', null, 'GET'))
            ->with(new RequestMatcher('^/images/' . $app['id_pattern'] . '$', null, 'GET'))
            ->with(new RequestMatcher('^/(places|labels)$', null, 'GET'))
            ->with(new RequestMatcher('^/organizers/suggest/.*', null, 'GET'))
            ->with(new RequestMatcher('^/jobs/', null, 'GET'))
    ],
    'cors-preflight' => array(
        'pattern' => $app['cors_preflight_request_matcher'],
    ),
    'secured' => array(
        'pattern' => '^.*$',
        'jwt' => [
            'uitid' => [
                'validation' => $app['config']['jwt']['uitid']['validation'],
                'required_claims' => [
                    'uid',
                    'nick',
                    'email',
                ],
                'public_key' => 'file://' . __DIR__ . '/../' . $app['config']['jwt']['uitid']['keys']['public']['file']
            ],
            'auth0' => [
                'validation' => $app['config']['jwt']['auth0']['validation'],
                'required_claims' => [
                    'email',
                    'sub',
                ],
                'public_key' => 'file://' . __DIR__ . '/../' . $app['config']['jwt']['auth0']['keys']['public']['file']
            ],
        ],
        'stateless' => true,
    ),
);

/**
 * Security services.
 */
$app->register(new \Silex\Provider\SecurityServiceProvider());
$app->register(new JwtServiceProvider());

$app['permissions_voter'] = $app->share(function($app) {
    return new PermissionsVoter($app['config']['user_permissions']);
});

$app['user_permissions_voter'] = $app->share(function($app) {
    return new UserPermissionsVoter($app[UserPermissionsServiceProvider::USER_PERMISSIONS_READ_REPOSITORY]);
});

$app['security.voters'] = $app->extend('security.voters', function($voters) use ($app){
    return array_merge(
        $voters,
        [
            $app['permissions_voter'],
            $app['user_permissions_voter'],
        ]
    );
});

$app['security.access_manager'] = $app->share(function($app) {
    return new AccessDecisionManager($app['security.voters'], AccessDecisionManager::STRATEGY_AFFIRMATIVE);
});

$app['security.access_rules'] = array(
    array(
        new RequestMatcher('^/labels/.*', null, ['POST', 'DELETE', 'PATCH']),
        Permission::LABELS_BEHEREN
    ),
    array('^/(roles|permissions|users)/.*', Permission::GEBRUIKERS_BEHEREN),
);

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
            /** @var \CultuurNet\UDB3\Http\Proxy\CdbXmlProxy $cdbXmlProxy */
            $cdbXmlProxy = $app['cdbxml_proxy'];

            return $cdbXmlProxy->handle($request);
        },
        Application::EARLY_EVENT
    );
}

if (isset($app['config']['calendar_summary_proxy']) &&
    $app['config']['calendar_summary_proxy']['enabled']) {
    $app->before(
        function (Request $request, Application $app) {
            /** @var \CultuurNet\UDB3\Http\Proxy\FilterPathMethodProxy $calendarSummaryProxy */
            $calendarSummaryProxy = $app['calendar_summary_proxy'];

            return $calendarSummaryProxy->handle($request);
        },
        Application::EARLY_EVENT
    );
}

if (isset($app['config']['search_proxy']) &&
    $app['config']['search_proxy']['enabled']) {
    $app->before(
        function (Request $request, Application $app) {
            /** @var \CultuurNet\UDB3\Http\Proxy\FilterPathMethodProxy $searchProxy */
            $searchProxy = $app['search_proxy'];

            return $searchProxy->handle($request);
        },
        Application::EARLY_EVENT
    );
}

$app->before(function (Request $request, Application $app) {
    $app['request_logger']->logRequest($request);
});

$app->after(function (Request $request, Response $response, Application $app) {
    $app['response_logger']->logResponse($request, $response);
});

$app->error(function (\Exception $e, $code) use ($app) {
    /** @var \Symfony\Component\HttpFoundation\RequestStack $requestStack */
    $requestStack = $app['request_stack'];
    $request = $requestStack->getCurrentRequest();
    $app['error_logger']->logError($e, $request, $code);
});

$app->mount('events/export', new \CultuurNet\UDB3\Silex\Export\ExportControllerProvider());

$app->get(
    'swagger.json',
    function () {
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

$app->mount(
    'variations',
    new FeatureControllerProvider(
        'variations',
        new \CultuurNet\UDB3\Silex\Variations\VariationsControllerProvider()
    )
);

$app->register(new \CultuurNet\UDB3\Silex\ErrorHandlerProvider());
/* @deprecated */
$app->mount('/', new \CultuurNet\UDB3\Silex\Place\DeprecatedPlaceControllerProvider());
$app->mount('/places', new \CultuurNet\UDB3\Silex\Place\PlaceControllerProvider());
$app->mount('/organizers', new \CultuurNet\UDB3\Silex\Organizer\OrganizerControllerProvider());
/* @deprecated */
$app->mount('/', new \CultuurNet\UDB3\Silex\Event\DeprecatedEventControllerProvider());
$app->mount('/events', new \CultuurNet\UDB3\Silex\Event\EventControllerProvider());
$app->mount('/', new \CultuurNet\UDB3\Silex\Media\MediaControllerProvider());
$app->mount('/', new \CultuurNet\UDB3\Silex\Offer\OfferControllerProvider());
$app->mount('/', new \CultuurNet\UDB3\Silex\Offer\BulkLabelOfferControllerProvider());
$app->mount('/', new \CultuurNet\UDB3\Silex\User\UserControllerProvider());
$app->mount('/', new \CultuurNet\UDB3\Silex\Role\RoleControllerProvider());
$app->mount('/labels', new \CultuurNet\UDB3\Silex\Labels\LabelsControllerProvider());
$app->mount('/jobs', new \CultuurNet\UDB3\Silex\Jobs\JobsControllerProvider());
$app->mount('/contexts', new \CultuurNet\UDB3\Silex\JSONLD\ContextControllerProvider());

$app->get(
    '/user',
    function (Application $app) {
        return (new JsonResponse())
            ->setData((object)[
                'id' => $app['current_user']->id,
                'nick' => $app['current_user']->nick,
                'email' => $app['current_user']->mbox,
            ])
            ->setPrivate();
    }
);

/**
 * Basic REST API for feature toggles.
 */
$app->mount('/', new \TwoDotsTwice\SilexFeatureToggles\FeatureTogglesControllerProvider());

$app->mount(ImportControllerProvider::PATH, new ImportControllerProvider());

$app->run();


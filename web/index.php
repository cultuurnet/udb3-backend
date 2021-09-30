<?php

require_once __DIR__ . '/../vendor/autoload.php';

use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use CultuurNet\UDB3\HttpFoundation\RequestMatcher\AnyOfRequestMatcher;
use CultuurNet\UDB3\HttpFoundation\RequestMatcher\PreflightRequestMatcher;
use CultuurNet\UDB3\HttpFoundation\Response\ApiProblemJsonResponse;
use CultuurNet\UDB3\Jwt\Silex\JwtServiceProvider;
use CultuurNet\UDB3\Jwt\Symfony\Authentication\JwtAuthenticationEntryPoint;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Silex\Error\WebErrorHandlerProvider;
use CultuurNet\UDB3\Silex\Error\ErrorLogger;
use CultuurNet\UDB3\Silex\Event\EventControllerProvider;
use CultuurNet\UDB3\Silex\Http\RequestHandlerControllerServiceProvider;
use CultuurNet\UDB3\Silex\Import\ImportControllerProvider;
use CultuurNet\UDB3\Silex\Offer\OfferControllerProvider;
use CultuurNet\UDB3\Silex\Place\PlaceControllerProvider;
use CultuurNet\UDB3\Silex\Role\UserPermissionsServiceProvider;
use CultuurNet\UDB3\Http\Management\PermissionsVoter;
use CultuurNet\UDB3\Http\Management\UserPermissionsVoter;
use CultuurNet\UDB3\Silex\UiTPASService\UiTPASServiceEventControllerProvider;
use CultuurNet\UDB3\Silex\UiTPASService\UiTPASServiceLabelsControllerProvider;
use CultuurNet\UDB3\Silex\UiTPASService\UiTPASServiceOrganizerControllerProvider;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManager;

/** @var Application $app */
$app = require __DIR__ . '/../bootstrap.php';

$app->register(new WebErrorHandlerProvider());

/**
 * Allow to use services as controllers.
 */
$app->register(new Silex\Provider\ServiceControllerServiceProvider());

/**
 * Allow to use class names of PSR-15 RequestHandlerInterface implementations as controllers.
 * The class name still needs to be registered as a service!
 */
$app->register(new RequestHandlerControllerServiceProvider());

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
            ->with(new RequestMatcher('^/(events|event|places|place)$', null, 'GET'))
            ->with(new RequestMatcher('^/(events|event|places|place)/$', null, 'GET'))
            ->with(new RequestMatcher('^/(events|event|places|place)/' . $app['id_pattern'] . '$', null, 'GET'))
            ->with(new RequestMatcher('^/(events|event|places|place)/' . $app['id_pattern'] . '$', null, 'GET'))
            ->with(new RequestMatcher('^/(events|event|places|place)/' . $app['id_pattern'] . '/calsum$', null, 'GET'))
            ->with(new RequestMatcher('^/(events|event|places|place)/' . $app['id_pattern'] . '/permissions/.+$', null, 'GET'))
            ->with(new RequestMatcher('^/(events|event|places|place)/' . $app['id_pattern'] . '/permission/.+$', null, 'GET'))
            ->with(new RequestMatcher('^/label/' . $app['id_pattern'] . '$', null, 'GET'))
            ->with(new RequestMatcher('^/organizers/' . $app['id_pattern'] . '$', null, 'GET'))
            ->with(new RequestMatcher('^/organizers/' . $app['id_pattern'] . '/permissions/.+$', null, 'GET'))
            ->with(new RequestMatcher('^/media/' . $app['id_pattern'] . '$', null, 'GET'))
            ->with(new RequestMatcher('^/images/' . $app['id_pattern'] . '$', null, 'GET'))
            ->with(new RequestMatcher('^/(labels)$', null, 'GET'))
            ->with(new RequestMatcher('^/organizers/suggest/.*', null, 'GET'))
            ->with(new RequestMatcher('^/jobs/', null, 'GET'))
            ->with(new RequestMatcher('^/uitpas/.*', null, 'GET'))
    ],
    'cors-preflight' => array(
        'pattern' => $app['cors_preflight_request_matcher'],
    ),
    'secured' => array(
        'pattern' => '^.*$',
        'jwt' => [
            'v1' => [
                'valid_issuers' => $app['config']['jwt']['v1']['valid_issuers'],
                'required_claims' => [
                    'uid',
                ],
                'public_key' => 'file://' . __DIR__ . '/../' . $app['config']['jwt']['v1']['keys']['public']['file']
            ],
            'v2' => [
                'valid_issuers' => $app['config']['jwt']['v2']['valid_issuers'],
                'required_claims' => [
                    'sub',
                ],
                'public_key' => 'file://' . __DIR__ . '/../' . $app['config']['jwt']['v2']['keys']['public']['file']
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

$app['security.entry_point.form._proto'] = $app::protect(
    function () use ($app) {
        return $app->share(
            function () {
                return new JwtAuthenticationEntryPoint();
            }
        );
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

$placeControllerProvider = new PlaceControllerProvider();
$placeOfferControllerProvider = new OfferControllerProvider(OfferType::PLACE());
$eventControllerProvider = new EventControllerProvider();
$eventOfferControllerProvider = new OfferControllerProvider(OfferType::EVENT());

$app->register($placeControllerProvider);
$app->register($placeOfferControllerProvider);
$app->register($eventControllerProvider);
$app->register($eventOfferControllerProvider);

$app->mount('/places', $placeControllerProvider);
$app->mount('/places', $placeOfferControllerProvider);

$app->mount('/events', $eventControllerProvider);
$app->mount('/events', $eventOfferControllerProvider);

// Workaround to make the old POST /place and POST /event work (without trailing slash).
// Those requests will not be handled by the PlaceControllerProvider and EventControllerProvider registered above,
// because those controller providers can only handle routes under /places/ and /events/ (note the trailing slash).
$app->post('/place', 'place_editing_controller:createPlace');
$app->post('/event', 'event_editing_controller:createEvent');

$app->mount('/organizers', new \CultuurNet\UDB3\Silex\Organizer\OrganizerControllerProvider());
$app->mount('/', new \CultuurNet\UDB3\Silex\Media\MediaControllerProvider());
$app->mount('/', new \CultuurNet\UDB3\Silex\Offer\BulkLabelOfferControllerProvider());
$app->mount('/', new \CultuurNet\UDB3\Silex\User\UserControllerProvider());
$app->mount('/', new \CultuurNet\UDB3\Silex\Role\RoleControllerProvider());
$app->mount('/labels', new \CultuurNet\UDB3\Silex\Labels\LabelsControllerProvider());
$app->mount('/jobs', new \CultuurNet\UDB3\Silex\Jobs\JobsControllerProvider());
$app->mount('/contexts', new \CultuurNet\UDB3\Silex\JSONLD\ContextControllerProvider());
$app->mount('/productions', new \CultuurNet\UDB3\Silex\Event\ProductionControllerProvider());
$app->mount('/uitpas/labels', new UiTPASServiceLabelsControllerProvider());
$app->mount('/uitpas/events', new UiTPASServiceEventControllerProvider());
$app->mount('/uitpas/organizers', new UiTPASServiceOrganizerControllerProvider());

$app->mount(ImportControllerProvider::PATH, new ImportControllerProvider());

// Match any path that does not match a registered route, rewrite it using a set of predefined pattern replacements and
// send an internal sub-request to try and match an existing route. If the sub-request does not return a response either
// a 404 will be returned.
// This makes it possible to support old endpoint names without having to register controllers/request handlers twice.
$app->match(
    '/{path}',
    function (Request $originalRequest, string $path) use ($app) {
        $rewrites = [
            '/^(event|place)($|\/.*)/' => '${1}s${2}', // Make /event(/...) and /place(/...) plural
        ];
        $path = preg_replace(array_keys($rewrites), array_values($rewrites), $path);
        $request = Request::create(
            $path,
            $originalRequest->getMethod(),
            [],
            $originalRequest->cookies->all(),
            $originalRequest->files->all(),
            $originalRequest->server->all(),
            $originalRequest->getContent()
        );
        $request = $request->duplicate(
            $originalRequest->query->all(),
            $originalRequest->request->all()
        );
        $request->headers->replace($app['request']->headers->all());
        return $app->handle($request, HttpKernelInterface::SUB_REQUEST);
    }
)->assert('path', '^.+$');

JsonSchemaLocator::setSchemaDirectory(__DIR__ . '/../vendor/publiq/stoplight-docs-uitdatabank/models');

try {
    $app->run();
} catch (\Throwable $throwable) {
    // All Silex kernel exceptions are caught by the ErrorHandlerProvider.
    // Errors and uncaught runtime exceptions are caught here.
    $app[ErrorLogger::class]->log($throwable);

    // Errors always get a status 500, but we still need a default status code in case of runtime exceptions that
    // weren't caught by Silex.
    $apiProblem = WebErrorHandlerProvider::createNewApiProblem(
        $throwable,
        500
    );

    // We're outside of the Silex app, so we cannot use the standard way to return a Response object.
    http_response_code($apiProblem->getStatus());
    header('Content-Type: application/json');
    echo json_encode($apiProblem->toArray());
    exit;
}

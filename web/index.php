<?php

require_once __DIR__ . '/../vendor/autoload.php';

use CultuurNet\UDB3\Http\Auth\RequestAuthenticator;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Silex\ApiName;
use CultuurNet\UDB3\Silex\Curators\CuratorsControllerProvider;
use CultuurNet\UDB3\Silex\Http\PsrRouterServiceProvider;
use CultuurNet\UDB3\Silex\Proxy\ProxyRequestHandlerServiceProvider;
use CultuurNet\UDB3\Silex\Udb3ControllerCollection;
use CultuurNet\UDB3\Silex\Error\WebErrorHandlerProvider;
use CultuurNet\UDB3\Silex\Error\ErrorLogger;
use CultuurNet\UDB3\Silex\Event\EventControllerProvider;
use CultuurNet\UDB3\Silex\Http\RequestHandlerControllerServiceProvider;
use CultuurNet\UDB3\Silex\Import\ImportControllerProvider;
use CultuurNet\UDB3\Silex\CatchAllRouteServiceProvider;
use CultuurNet\UDB3\Silex\Offer\OfferControllerProvider;
use CultuurNet\UDB3\Silex\Place\PlaceControllerProvider;
use CultuurNet\UDB3\Silex\UiTPASService\UiTPASServiceEventControllerProvider;
use CultuurNet\UDB3\Silex\UiTPASService\UiTPASServiceLabelsControllerProvider;
use CultuurNet\UDB3\Silex\UiTPASService\UiTPASServiceOrganizerControllerProvider;
use Silex\Application;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

const API_NAME = ApiName::JSONLD;

/** @var Application $app */
$app = require __DIR__ . '/../bootstrap.php';

// Register our own ControllerCollection as controllers_factory so every route automatically gets a trailing slash.
$app['controllers_factory'] = function () use ($app) {
    return new Udb3ControllerCollection($app['route_factory']);
};

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
 * Register a PSR-7 / PSR-15 compatible router.
 * Will be used in CatchAllRouteServiceProvider to route unmatched requests from Silex to the PSR router, until we can
 * completely by-pass the Silex router.
 */
$app->register(new PsrRouterServiceProvider());

/**
 * Register service providers for request handlers.
 */
$app->register(new ProxyRequestHandlerServiceProvider());

/**
 * Middleware that authenticates incoming HTTP requests using the RequestAuthenticator service.
 * @todo III-4235 Move to Middleware in new PSR router when all routes are registered on the new router.
 */
$app->before(
    static function (Request $request, Application $app): void {
        $psrRequest = (new DiactorosFactory())->createRequest($request);

        /** @var RequestAuthenticator $authenticator */
        $authenticator = $app[RequestAuthenticator::class];
        $authenticator->authenticate($psrRequest);
    },
    Application::EARLY_EVENT
);

$app->mount('events/export', new \CultuurNet\UDB3\Silex\Export\ExportControllerProvider());

$app->mount('saved-searches', new \CultuurNet\UDB3\Silex\SavedSearches\SavedSearchesControllerProvider());

$placeControllerProvider = new PlaceControllerProvider();
$eventControllerProvider = new EventControllerProvider();
$offerControllerProvider = new OfferControllerProvider();

$app->register($placeControllerProvider);
$app->register($eventControllerProvider);
$app->register($offerControllerProvider);

$app->mount('/places', $placeControllerProvider);

$app->mount('/events', $eventControllerProvider);

$app->mount('/', $offerControllerProvider);

$app->mount('/organizers', new \CultuurNet\UDB3\Silex\Organizer\OrganizerControllerProvider());
$app->mount('/', new \CultuurNet\UDB3\Silex\Media\MediaControllerProvider());
$app->mount('/', new \CultuurNet\UDB3\Silex\Offer\BulkLabelOfferControllerProvider());
$app->mount('/', new \CultuurNet\UDB3\Silex\User\UserControllerProvider());
$app->mount('/', new \CultuurNet\UDB3\Silex\Role\RoleControllerProvider());
$app->mount('/labels', new \CultuurNet\UDB3\Silex\Labels\LabelsControllerProvider());
$app->mount('/jobs', new \CultuurNet\UDB3\Silex\Jobs\JobsControllerProvider());
$app->mount('/productions', new \CultuurNet\UDB3\Silex\Event\ProductionControllerProvider());
$app->mount('/uitpas/labels', new UiTPASServiceLabelsControllerProvider());
$app->mount('/uitpas/events', new UiTPASServiceEventControllerProvider());
$app->mount('/uitpas/organizers', new UiTPASServiceOrganizerControllerProvider());
$app->mount('/news-articles', new CuratorsControllerProvider());

$app->mount(ImportControllerProvider::PATH, new ImportControllerProvider());

// Match with any OPTIONS request with any URL and return a 204 No Content. Actual CORS headers will be added by an
// ->after() middleware, which adds CORS headers to every request (so non-preflighted requests like simple GETs also get
// the needed CORS headers).
// Note that the new PSR router in PsrRouterServiceProvider already supports OPTIONS requests for all routes registered
// in the new router, so this can be removed completely once all route definitions and handlers are moved to the new
// router.
$app->options('/{path}', fn () => new NoContentResponse())->assert('path', '^.+$');

// Add CORS headers to every request. We explicitly allow everything, because we don't use cookies and our API is not on
// an internal network, so CORS requests are never a security issue in our case. This greatly reduces the risk of CORS
// bugs in our frontend and other integrations.
// @todo III-4235 Move to Middleware in new PSR router when all routes are registered on the new router.
$app->after(
    function (Request $request, Response $response) {
        // Allow any known method regardless of the URL.
        $methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];
        $response->headers->set('Allow', implode(',', $methods));
        $response->headers->set('Access-Control-Allow-Methods', implode(',', $methods));

        // Allow the Authorization header to be used.
        $response->headers->set('Access-Control-Allow-Credentials', 'true');

        // If a specific origin has been requested to be used, echo it back. Otherwise allow *.
        $requestedOrigin = $request->headers->get('Origin', '*');
        $response->headers->set('Access-Control-Allow-Origin', $requestedOrigin);

        // If specific headers have been requested to be used, echo them back. Otherwise allow the default headers.
        $requestedHeaders = $request->headers->get('Access-Control-Request-Headers', 'authorization,x-api-key');
        $response->headers->set('Access-Control-Allow-Headers', $requestedHeaders);
    }
);

$app->register(new CatchAllRouteServiceProvider());

JsonSchemaLocator::setSchemaDirectory(__DIR__ . '/../vendor/publiq/udb3-json-schemas');

try {
    $app->run();
} catch (\Throwable $throwable) {
    // All Silex kernel exceptions are caught by the ErrorHandlerProvider.
    // Errors and uncaught runtime exceptions are caught here.
    $app[ErrorLogger::class]->log($throwable);

    // Errors always get a status 500, but we still need a default status code in case of runtime exceptions that
    // weren't caught by Silex.
    $apiProblem = WebErrorHandlerProvider::createNewApiProblem(
        $app['request_stack']->getCurrentRequest(),
        $throwable,
        500
    );

    // We're outside of the Silex app, so we cannot use the standard way to return a Response object.
    http_response_code($apiProblem->getStatus());
    header('Content-Type: application/json');
    echo json_encode($apiProblem->toArray());
    exit;
}

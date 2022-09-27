<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use CultuurNet\UDB3\Http\LegacyPathRewriter;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use CultuurNet\UDB3\Silex\ApiName;
use CultuurNet\UDB3\Silex\Error\WebErrorHandler;
use CultuurNet\UDB3\Silex\Http\PsrRouterServiceProvider;
use CultuurNet\UDB3\Silex\Proxy\ProxyRequestHandlerServiceProvider;
use CultuurNet\UDB3\Silex\Udb3ControllerCollection;
use CultuurNet\UDB3\Silex\Error\WebErrorHandlerProvider;
use CultuurNet\UDB3\Silex\Http\RequestHandlerControllerServiceProvider;
use CultuurNet\UDB3\Silex\CatchAllRouteServiceProvider;
use Laminas\HttpHandlerRunner\Emitter\SapiStreamEmitter;
use League\Route\Router;
use Silex\Application;
use Slim\Psr7\Factory\ServerRequestFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;

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

$app->register(new CatchAllRouteServiceProvider());

JsonSchemaLocator::setSchemaDirectory(__DIR__ . '/../vendor/publiq/udb3-json-schemas');

$request = ServerRequestFactory::createFromGlobals();
$request = (new LegacyPathRewriter())->rewriteRequest($request);

try {
    $response = $app[Router::class]->handle($request);
} catch (\Throwable $throwable) {
    /** @var WebErrorHandler $webErrorHandler */
    $webErrorHandler = $app[WebErrorHandler::class];
    $request = (new DiactorosFactory())->createRequest($app['request_stack']->getCurrentRequest());
    $response = $webErrorHandler->handle($request, $throwable);
}

(new SapiStreamEmitter())->emit($response);

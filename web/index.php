<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use CultuurNet\UDB3\Http\LegacyPathRewriter;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use CultuurNet\UDB3\Silex\ApiName;
use CultuurNet\UDB3\Silex\Container\HybridContainerApplication;
use CultuurNet\UDB3\Silex\Error\WebErrorHandler;
use CultuurNet\UDB3\Silex\Http\PsrRouterServiceProvider;
use CultuurNet\UDB3\Silex\Proxy\ProxyRequestHandlerServiceProvider;
use CultuurNet\UDB3\Silex\Error\WebErrorHandlerProvider;
use Laminas\HttpHandlerRunner\Emitter\SapiStreamEmitter;
use League\Route\Router;
use Slim\Psr7\Factory\ServerRequestFactory;

const API_NAME = ApiName::JSONLD;

/** @var HybridContainerApplication $app */
$app = require __DIR__ . '/../bootstrap.php';
$container = $app->getLeagueContainer();

$container->addServiceProvider(new WebErrorHandlerProvider());

/**
 * Register a PSR-7 / PSR-15 compatible router.
 * Will be used in CatchAllRouteServiceProvider to route unmatched requests from Silex to the PSR router, until we can
 * completely by-pass the Silex router.
 */
$container->addServiceProvider(new PsrRouterServiceProvider());

/**
 * Register service providers for request handlers.
 */
$app->register(new ProxyRequestHandlerServiceProvider());

JsonSchemaLocator::setSchemaDirectory(__DIR__ . '/../vendor/publiq/udb3-json-schemas');

$request = ServerRequestFactory::createFromGlobals();
$request = (new LegacyPathRewriter())->rewriteRequest($request);

// If the Router and all its dependencies (i.e. other services requires to handle the request) are instantiated
// correctly, any exception will be caught by the Router and then converted to an ApiProblem using the
// CustomLeagueRouterStrategy and WebErrorHandler. However if the instantiation of a service fails, we need to catch the
// resulting Throwable and convert it to an ApiProblem response here using the WebErrorHandler.
try {
    $response = $container->get(Router::class)->handle($request);
} catch (\Throwable $throwable) {
    /** @var WebErrorHandler $webErrorHandler */
    $webErrorHandler = $container->get(WebErrorHandler::class);
    $response = $webErrorHandler->handle($request, $throwable);
}

(new SapiStreamEmitter())->emit($response);

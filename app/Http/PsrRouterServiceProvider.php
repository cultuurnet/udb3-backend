<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Http;

use CultuurNet\UDB3\Http\Offer\GetDetailRequestHandler;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use League\Route\Router;
use Silex\Application;
use Silex\ServiceProviderInterface;

final class PsrRouterServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app): void
    {
        $app[Router::class] = $app::share(
            function (Application $app) {
                $router = new Router();

                // Return 204 No Content for every OPTIONS request to support CORS.
                // The necessary CORS headers will be added by a Middleware that adds them for every response.
                $router->options('/{path:.*}', fn () => new NoContentResponse());

                $router->get('/{offerType}/{offerId}', [$app[GetDetailRequestHandler::class], 'handle']);

                return $router;
            }
        );
    }

    public function boot(Application $app): void
    {
    }
}

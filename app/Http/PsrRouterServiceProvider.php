<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Http;

use CultuurNet\UDB3\Silex\Http\CustomLeagueRouterStrategy;
use CultuurNet\UDB3\Http\InvokableRequestHandlerContainer;
use CultuurNet\UDB3\Http\Offer\GetDetailRequestHandler;
use CultuurNet\UDB3\Silex\PimplePSRContainerBridge;
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

                // Create a PSR container based on the Silex (Pimple) container, to allow the router to resolve
                // request handler class names to actual instances.
                $container = new PimplePSRContainerBridge($app);

                // Decorate the PSR container with InvokableRequestHandlerContainer so that every
                // RequestHandlerInterface that gets requested by the router is decorated with InvokableRequestHandler,
                // because the League router needs the router to be a callable at the time of writing.
                $container = new InvokableRequestHandlerContainer($container);

                // Use a custom strategy so we can implement getOptionsCallable() on the strategy, to support CORS
                // pre-flight requests. We also have to set the container on the strategy.
                $routerStrategy = new CustomLeagueRouterStrategy();
                $routerStrategy->setContainer($container);
                $router->setStrategy($routerStrategy);

                $router->get('/{offerType:events|places}/{offerId}/', GetDetailRequestHandler::class);

                return $router;
            }
        );
    }

    public function boot(Application $app): void
    {
    }
}

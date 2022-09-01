<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Http;

use CultuurNet\UDB3\Http\CustomLeagueRouterStrategy;
use CultuurNet\UDB3\Http\LazyLoadingRequestHandler;
use CultuurNet\UDB3\Http\Offer\GetDetailRequestHandler;
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
                $router->setStrategy(new CustomLeagueRouterStrategy());

                $router->get('/{offerType:events|places}/{offerId}/', new LazyLoadingRequestHandler($app, GetDetailRequestHandler::class));

                return $router;
            }
        );
    }

    public function boot(Application $app): void
    {
    }
}

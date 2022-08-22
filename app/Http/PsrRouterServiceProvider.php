<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Http;

use League\Route\Router;
use Silex\Application;
use Silex\ServiceProviderInterface;

final class PsrRouterServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app): void
    {
        $app[Router::class] = $app::share(
            fn () => new Router()
        );
    }

    public function boot(Application $app): void
    {
    }
}

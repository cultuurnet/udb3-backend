<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Http;

use Silex\Application;
use Silex\ServiceProviderInterface;

/**
 * Makes it possible to register an instance of RequestHandlerInterface (PSR-15) as a route controller.
 * Approach copied from ServiceControllerServiceProvider, which is an official Silex service provider that we used to
 * register controllers as "service:method" strings.
 */
final class RequestHandlerControllerServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['resolver'] = $app->share(
            $app->extend('resolver', function ($resolver) use ($app) {
                return new RequestHandlerControllerResolver($resolver, $app);
            })
        );
    }

    public function boot(Application $app)
    {
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Proxy;

use CultuurNet\UDB3\Http\Proxy\ProxyRequestHandler;
use GuzzleHttp\Client;
use Silex\Application;
use Silex\ServiceProviderInterface;

final class ProxyRequestHandlerServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app): void
    {
        $app[ProxyRequestHandler::class] = $app::share(
            static function (Application $app) {
                return new ProxyRequestHandler(
                    $app['config']['search_proxy']['redirect_domain'],
                    new Client()
                );
            }
        );
    }

    public function boot(Application $app): void
    {
    }
}

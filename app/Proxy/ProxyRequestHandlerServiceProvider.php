<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Proxy;

use CultuurNet\UDB3\Http\Proxy\ProxyRequestHandler;
use GuzzleHttp\Client;
use League\Uri\Uri;
use Silex\Application;
use Silex\ServiceProviderInterface;

final class ProxyRequestHandlerServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app): void
    {
        $app[ProxyRequestHandler::class] = $app::share(
            static function (Application $app) {
                return new ProxyRequestHandler(
                    Uri::createFromString($app['config']['search']['v3']['base_url'])->getHost(),
                    new Client()
                );
            }
        );
    }

    public function boot(Application $app): void
    {
    }
}

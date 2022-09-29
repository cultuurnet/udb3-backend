<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Proxy;

use CultuurNet\UDB3\Http\Proxy\ProxyRequestHandler;
use GuzzleHttp\Client;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Uri\Uri;
use Silex\Application;

final class ProxyRequestHandlerServiceProvider extends AbstractServiceProvider
{
    public function provides(string $id): bool
    {
        $services = [ProxyRequestHandler::class];
        return in_array($id, $services, true);
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            ProxyRequestHandler::class,
            static function (Application $app) {
                return new ProxyRequestHandler(
                    Uri::createFromString($app['config']['search']['v3']['base_url'])->getHost(),
                    new Client()
                );
            }
        );
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Proxy;

use CultuurNet\UDB3\Http\Proxy\ProxyRequestHandler;
use CultuurNet\UDB3\Silex\Container\AbstractServiceProvider;
use GuzzleHttp\Client;
use League\Uri\Uri;
use Silex\Application;

final class ProxyRequestHandlerServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [ProxyRequestHandler::class];
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

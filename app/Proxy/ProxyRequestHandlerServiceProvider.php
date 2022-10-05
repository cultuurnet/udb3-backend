<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Proxy;

use CultuurNet\UDB3\Http\Proxy\ProxyRequestHandler;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use GuzzleHttp\Client;
use League\Uri\Uri;

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
            static function () use ($container) {
                return new ProxyRequestHandler(
                    Uri::createFromString($container->get('config')['search']['v3']['base_url'])->getHost(),
                    new Client()
                );
            }
        );
    }
}

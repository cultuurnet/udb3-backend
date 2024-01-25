<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http;

use CultuurNet\UDB3\Container\AbstractServiceProvider;

final class MiddlewareServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            CheckTypeOfOfferMiddleware::class,
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            CheckTypeOfOfferMiddleware::class,
            fn () => new CheckTypeOfOfferMiddleware(
                $container->get('place_jsonld_cache'),
                $container->get('event_jsonld_cache')
            )
        );
    }
}

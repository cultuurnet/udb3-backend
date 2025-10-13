<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Address;

use CultuurNet\UDB3\Address\StreetSuggester\BPostStreetSuggester;
use CultuurNet\UDB3\Address\StreetSuggester\CachedStreetSuggester;
use CultuurNet\UDB3\Address\StreetSuggester\StreetSuggester;
use CultuurNet\UDB3\Cache\CacheFactory;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Http\Address\GetStreetRequestHandler;
use GuzzleHttp\Client;

class AddressServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            StreetSuggester::class,
            GetStreetRequestHandler::class,
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            StreetSuggester::class,
            fn () => new CachedStreetSuggester(
                new BPostStreetSuggester(
                    new Client(),
                    $container->get('config')['bpost']['domain'],
                    $container->get('config')['bpost']['stage'],
                    $container->get('config')['bpost']['token'],
                ),
                CacheFactory::create(
                    $container->get('app_cache'),
                    'belgium_streets',
                    86400
                )
            )
        );

        $container->addShared(
            GetStreetRequestHandler::class,
            fn () => new GetStreetRequestHandler(
                $container->get(StreetSuggester::class)
            )
        );
    }
}

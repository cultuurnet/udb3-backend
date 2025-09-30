<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Addresses;

use CultuurNet\UDB3\Address\StreetSuggester\BPostStreetSuggester;
use CultuurNet\UDB3\Address\StreetSuggester\StreetSuggester;
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
            fn () => new BPostStreetSuggester(
                new Client(),
                $container->get('config')['bpost']['domain'],
                $container->get('config')['bpost']['token'],
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

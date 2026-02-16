<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Taxonomy;

use CultuurNet\UDB3\Container\AbstractServiceProvider;
use GuzzleHttp\Client;

final class TaxonomyServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            TaxonomyApiClient::class,
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            TaxonomyApiClient::class,
            fn () => new JsonTaxonomyApiClient(
                new Client(),
                $container->get('config')['taxonomy']['terms']
            )
        );
    }
}

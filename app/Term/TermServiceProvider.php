<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Term;

use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Error\LoggerFactory;
use CultuurNet\UDB3\Error\LoggerName;
use CultuurNet\UDB3\Taxonomy\JsonTaxonomyApiClient;
use CultuurNet\UDB3\Taxonomy\TaxonomyApiClient;
use GuzzleHttp\Client;

final class TermServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            TaxonomyApiClient::class,
            TermRepository::class,
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            TaxonomyApiClient::class,
            fn () => new JsonTaxonomyApiClient(
                new Client(),
                $container->get('config')['taxonomy']['terms'],
                LoggerFactory::create($this->getContainer(), LoggerName::forWeb())
            )
        );

        $container->addShared(
            TermRepository::class,
            function () use ($container): TermRepository {
                /** @var TaxonomyApiClient $taxonomyApiClient */
                $taxonomyApiClient = $container->get(TaxonomyApiClient::class);
                return new TermRepository($taxonomyApiClient->getMapping());
            }
        );
    }
}

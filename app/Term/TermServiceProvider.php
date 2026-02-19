<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Term;

use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Model\Import\Place\PlaceCategoryResolver;
use CultuurNet\UDB3\Place\PlaceFacilityResolver;
use CultuurNet\UDB3\Place\PlaceTypeResolver;
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
            PlaceTypeResolver::class,
            PlaceFacilityResolver::class,
            PlaceCategoryResolver::class,
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

        $container->addShared(
            TermRepository::class,
            function () use ($container): TermRepository {
                /** @var TaxonomyApiClient $taxonomyApiClient */
                $taxonomyApiClient = $container->get(TaxonomyApiClient::class);
                return new TermRepository($taxonomyApiClient->getMapping());
            }
        );

        $container->addShared(
            PlaceTypeResolver::class,
            function () use ($container): PlaceTypeResolver {
                /** @var TaxonomyApiClient $taxonomyApiClient */
                $taxonomyApiClient = $container->get(TaxonomyApiClient::class);
                return (new PlaceTypeResolver())->withTypesFromApi($taxonomyApiClient->getPlaceTypes());
            }
        );

        $container->addShared(
            PlaceFacilityResolver::class,
            fn () => new PlaceFacilityResolver()
        );

        $container->addShared(
            PlaceCategoryResolver::class,
            fn () => new PlaceCategoryResolver(
                $container->get(PlaceTypeResolver::class),
                $container->get(PlaceFacilityResolver::class)
            )
        );
    }
}

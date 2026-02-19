<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Term;

use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Event\EventFacilityResolver;
use CultuurNet\UDB3\Event\EventThemeResolver;
use CultuurNet\UDB3\Event\EventTypeResolver;
use CultuurNet\UDB3\Model\Import\Event\EventCategoryResolver;
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
            EventTypeResolver::class,
            EventFacilityResolver::class,
            EventThemeResolver::class,
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
            EventTypeResolver::class,
            function () use ($container): EventTypeResolver {
                /** @var TaxonomyApiClient $taxonomyApiClient */
                $taxonomyApiClient = $container->get(TaxonomyApiClient::class);
                return new EventTypeResolver($taxonomyApiClient->getEventTypes());
            }
        );

        $container->addShared(
            EventFacilityResolver::class,
            function () use ($container): EventFacilityResolver {
                /** @var TaxonomyApiClient $taxonomyApiClient */
                $taxonomyApiClient = $container->get(TaxonomyApiClient::class);
                return new EventFacilityResolver($taxonomyApiClient->getEventFacilities());
            }
        );

        $container->addShared(
            EventThemeResolver::class,
            fn () => new EventThemeResolver()
        );

        $container->addShared(
            EventCategoryResolver::class,
            fn () => new EventCategoryResolver($container->get(EventTypeResolver::class), $container->get(EventFacilityResolver::class), $container->get(EventThemeResolver::class))
        );

        $container->addShared(
            PlaceTypeResolver::class,
            function () use ($container): PlaceTypeResolver {
                /** @var TaxonomyApiClient $taxonomyApiClient */
                $taxonomyApiClient = $container->get(TaxonomyApiClient::class);
                return new PlaceTypeResolver($taxonomyApiClient->getPlaceTypes());
            }
        );

        $container->addShared(
            PlaceFacilityResolver::class,
            function () use ($container): PlaceFacilityResolver {
                /** @var TaxonomyApiClient $taxonomyApiClient */
                $taxonomyApiClient = $container->get(TaxonomyApiClient::class);
                return new PlaceFacilityResolver($taxonomyApiClient->getPlaceFacilities());
            }
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

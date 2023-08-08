<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

use CultuurNet\UDB3\Container\AbstractServiceProvider;

final class Sapi3SearchServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            OffersSapi3SearchService::class,
            EventsSapi3SearchService::class,
            PlacesSapi3SearchService::class,
            OrganizersSapi3SearchService::class,
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            OffersSapi3SearchService::class,
            function () use ($container) {
                return SearchSapi3ServiceFactory::createOffersSearchService($container);
            }
        );

        $container->addShared(
            EventsSapi3SearchService::class,
            function () use ($container) {
                return SearchSapi3ServiceFactory::createEventsSearchService($container);
            }
        );

        $container->addShared(
            PlacesSapi3SearchService::class,
            function () use ($container) {
                return SearchSapi3ServiceFactory::createPlacesSearchService($container);
            }
        );

        $container->addShared(
            OrganizersSapi3SearchService::class,
            function () use ($container) {
                return SearchSapi3ServiceFactory::createOrganizerSearchService($container);
            }
        );
    }
}

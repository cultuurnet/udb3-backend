<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

use CultuurNet\UDB3\Container\AbstractServiceProvider;

final class Sapi3SearchServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            Sapi3OffersSearchService::class,
            Sapi3EventsSearchService::class,
            Sapi3PlacesSearchService::class,
            Sapi3OrganizersSearchService::class,
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            Sapi3OffersSearchService::class,
            function () use ($container) {
                return Sapi3SearchServiceFactory::createOffersSearchService($container);
            }
        );

        $container->addShared(
            Sapi3EventsSearchService::class,
            function () use ($container) {
                return Sapi3SearchServiceFactory::createEventsSearchService($container);
            }
        );

        $container->addShared(
            Sapi3PlacesSearchService::class,
            function () use ($container) {
                return Sapi3SearchServiceFactory::createPlacesSearchService($container);
            }
        );

        $container->addShared(
            Sapi3OrganizersSearchService::class,
            function () use ($container) {
                return Sapi3SearchServiceFactory::createOrganizerSearchService($container);
            }
        );
    }
}

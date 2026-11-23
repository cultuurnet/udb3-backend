<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

use CultuurNet\UDB3\Container\AbstractServiceProvider;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Uri;

final class Sapi3SearchServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            OffersSapi3SearchService::class,
            EventsSapi3SearchService::class,
            PlacesSapi3SearchService::class,
            OrganizersSapi3SearchService::class,
            CalendarSummaryService::class,
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

        $container->addShared(
            CalendarSummaryService::class,
            fn (): CalendarSummaryService => new Sapi3CalendarSummaryService(
                new Uri($container->get('config')['search']['v3']['base_url']),
                new Client([
                    'connect_timeout' => $container->get('config')['search']['v3']['connect_timeout'] ?? 2,
                    'timeout' => $container->get('config')['search']['v3']['timeout'] ?? 5,
                ]),
                $container->get('config')['search']['v3']['api_key'] ?? null
            )
        );
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Search;

use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemIdentifierFactory;
use CultuurNet\UDB3\Search\Sapi3SearchService;
use GuzzleHttp\Psr7\Uri;
use Http\Adapter\Guzzle7\Client;

final class Sapi3SearchServiceProvider extends AbstractServiceProvider
{
    public const SEARCH_SERVICE_OFFERS = 'sapi3_search_service_offers';
    public const SEARCH_SERVICE_EVENTS = 'sapi3_search_service_events';
    public const SEARCH_SERVICE_PLACES = 'sapi3_search_service_places';
    public const SEARCH_SERVICE_ORGANIZERS = 'sapi3_search_service_organizers';

    protected function getProvidedServiceNames(): array
    {
        return [
            self::SEARCH_SERVICE_OFFERS,
            self::SEARCH_SERVICE_EVENTS,
            self::SEARCH_SERVICE_PLACES,
            self::SEARCH_SERVICE_ORGANIZERS,
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            self::SEARCH_SERVICE_OFFERS,
            function () use ($container) {
                return new Sapi3SearchService(
                    new Uri($container->get('config')['search']['v3']['base_url'] . '/offers/'),
                    new Client(new \GuzzleHttp\Client()),
                    new ItemIdentifierFactory($container->get('config')['item_url_regex']),
                    $container->get('config')['export']['search']['api_key'] ?? null
                );
            }
        );


        $container->addShared(
            self::SEARCH_SERVICE_EVENTS,
            function () use ($container) {
                return new Sapi3SearchService(
                    new Uri($container->get('config')['search']['v3']['base_url'] . '/events/'),
                    new Client(new \GuzzleHttp\Client()),
                    new ItemIdentifierFactory($container->get('config')['item_url_regex']),
                    $container->get('config')['export']['search']['api_key'] ?? null
                );
            }
        );


        $container->addShared(
            self::SEARCH_SERVICE_PLACES,
            function () use ($container) {
                return new Sapi3SearchService(
                    new Uri($container->get('config')['search']['v3']['base_url'] . '/places/'),
                    new Client(new \GuzzleHttp\Client()),
                    new ItemIdentifierFactory($container->get('config')['item_url_regex']),
                    $container->get('config')['export']['search']['api_key'] ?? null
                );
            }
        );

        $container->addShared(
            self::SEARCH_SERVICE_ORGANIZERS,
            function () use ($container) {
                return new Sapi3SearchService(
                    new Uri($container->get('config')['search']['v3']['base_url'] . '/organizers/'),
                    new Client(new \GuzzleHttp\Client()),
                    new ItemIdentifierFactory($container->get('config')['item_url_regex']),
                    $container->get('config')['export']['search']['api_key'] ?? null
                );
            }
        );
    }
}

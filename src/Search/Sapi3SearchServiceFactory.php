<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

use CultuurNet\UDB3\Model\ValueObject\Identity\ItemIdentifierFactory;
use GuzzleHttp\Psr7\Uri;
use Http\Adapter\Guzzle7\Client;
use League\Container\DefinitionContainerInterface;

final class Sapi3SearchServiceFactory
{
    public static function createOrganizerSearchService(DefinitionContainerInterface $container): SearchServiceInterface
    {
        return new Sapi3OrganizersSearchService(
            new Uri($container->get('config')['search']['v3']['base_url'] . '/organizers/'),
            new Client(new \GuzzleHttp\Client()),
            new ItemIdentifierFactory($container->get('config')['item_url_regex']),
            $container->get('config')['export']['search']['api_key'] ?? null
        );
    }

    public static function createPlacesSearchService(DefinitionContainerInterface $container): SearchServiceInterface
    {
        return new Sapi3PlacesSearchService(
            new Uri($container->get('config')['search']['v3']['base_url'] . '/places/'),
            new Client(new \GuzzleHttp\Client()),
            new ItemIdentifierFactory($container->get('config')['item_url_regex']),
            $container->get('config')['export']['search']['api_key'] ?? null
        );
    }

    public static function createEventsSearchService(DefinitionContainerInterface $container): SearchServiceInterface
    {
        return new Sapi3EventsSearchService(
            new Uri($container->get('config')['search']['v3']['base_url'] . '/events/'),
            new Client(new \GuzzleHttp\Client()),
            new ItemIdentifierFactory($container->get('config')['item_url_regex']),
            $container->get('config')['export']['search']['api_key'] ?? null
        );
    }

    public static function createOffersSearchService(DefinitionContainerInterface $container): SearchServiceInterface
    {
        return new Sapi3OffersSearchService(
            new Uri($container->get('config')['search']['v3']['base_url'] . '/offers/'),
            new Client(new \GuzzleHttp\Client()),
            new ItemIdentifierFactory($container->get('config')['item_url_regex']),
            $container->get('config')['export']['search']['api_key'] ?? null
        );
    }
}

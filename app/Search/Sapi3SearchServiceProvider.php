<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Search;

use CultuurNet\UDB3\Model\ValueObject\Identity\ItemIdentifierFactory;
use CultuurNet\UDB3\Search\Sapi3SearchService;
use GuzzleHttp\Psr7\Uri;
use Http\Adapter\Guzzle6\Client;
use Silex\Application;
use Silex\ServiceProviderInterface;

class Sapi3SearchServiceProvider implements ServiceProviderInterface
{
    public const SEARCH_SERVICE_OFFERS = 'sapi3_search_service_offers';
    public const SEARCH_SERVICE_EVENTS = 'sapi3_search_service_events';
    public const SEARCH_SERVICE_PLACES = 'sapi3_search_service_places';
    public const SEARCH_SERVICE_ORGANIZERS = 'sapi3_search_service_organizers';

    public function register(Application $app): void
    {
        $app[self::SEARCH_SERVICE_OFFERS] = $app->share(
            function ($app) {
                return new Sapi3SearchService(
                    new Uri($app['config']['search']['v3']['base_url'] . '/offers/'),
                    new Client(new \GuzzleHttp\Client()),
                    new ItemIdentifierFactory($app['config']['item_url_regex']),
                    $app['config']['export']['search']['api_key'] ?? null
                );
            }
        );

        $app[self::SEARCH_SERVICE_EVENTS] = $app->share(
            function ($app) {
                return new Sapi3SearchService(
                    new Uri($app['config']['search']['v3']['base_url'] . '/events/'),
                    new Client(new \GuzzleHttp\Client()),
                    new ItemIdentifierFactory($app['config']['item_url_regex']),
                    $app['config']['export']['search']['api_key'] ?? null
                );
            }
        );

        $app[self::SEARCH_SERVICE_PLACES] = $app->share(
            function ($app) {
                return new Sapi3SearchService(
                    new Uri($app['config']['search']['v3']['base_url'] . '/places/'),
                    new Client(new \GuzzleHttp\Client()),
                    new ItemIdentifierFactory($app['config']['item_url_regex']),
                    $app['config']['export']['search']['api_key'] ?? null
                );
            }
        );

        $app[self::SEARCH_SERVICE_ORGANIZERS] = $app->share(
            function ($app) {
                return new Sapi3SearchService(
                    new Uri($app['config']['search']['v3']['base_url'] . '/organizers/'),
                    new Client(new \GuzzleHttp\Client()),
                    new ItemIdentifierFactory($app['config']['item_url_regex']),
                    $app['config']['export']['search']['api_key'] ?? null
                );
            }
        );
    }

    public function boot(Application $app): void
    {
    }
}

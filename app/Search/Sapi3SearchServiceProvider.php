<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Search;

use CultuurNet\UDB3\Search\ResultsGenerator;
use Http\Adapter\Guzzle6\Client;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Silex\Application;
use Silex\ServiceProviderInterface;

class Sapi3SearchServiceProvider implements ServiceProviderInterface
{
    public const SEARCH_SERVICE_OFFERS = 'sapi3_search_service_offers';
    public const SEARCH_SERVICE_EVENTS = 'sapi3_search_service_events';
    public const SEARCH_SERVICE_PLACES = 'sapi3_search_service_places';
    public const ORGANIZERS_COUNTING_SEARCH_SERVICE = 'sapi3_organizers_counting_service';
    public const OFFERS_COUNTING_SEARCH_SERVICE = 'sapi3_offers_counting_service';

    public function register(Application $app)
    {
        $app[self::SEARCH_SERVICE_OFFERS] = $app->share(
            function ($app) {
                return new \CultuurNet\UDB3\Search\Sapi3SearchService(
                    new \GuzzleHttp\Psr7\Uri($app['config']['search']['v3']['base_url'] . '/offers/'),
                    new Client(new \GuzzleHttp\Client()),
                    $app['iri_offer_identifier_factory'],
                    $app['config']['export']['search']['api_key'] ?? null
                );
            }
        );

        $app[self::SEARCH_SERVICE_EVENTS] = $app->share(
            function ($app) {
                return new \CultuurNet\UDB3\Search\Sapi3SearchService(
                    new \GuzzleHttp\Psr7\Uri($app['config']['search']['v3']['base_url'] . '/events/'),
                    new Client(new \GuzzleHttp\Client()),
                    $app['iri_offer_identifier_factory'],
                    $app['config']['export']['search']['api_key'] ?? null
                );
            }
        );

        $app[self::SEARCH_SERVICE_PLACES] = $app->share(
            function ($app) {
                return new \CultuurNet\UDB3\Search\Sapi3SearchService(
                    new \GuzzleHttp\Psr7\Uri($app['config']['search']['v3']['base_url'] . '/places/'),
                    new Client(new \GuzzleHttp\Client()),
                    $app['iri_offer_identifier_factory'],
                    $app['config']['export']['search']['api_key'] ?? null
                );
            }
        );

        $app[self::OFFERS_COUNTING_SEARCH_SERVICE] = $app->share(
            function ($app) {
                $search = new \CultuurNet\UDB3\Search\Sapi3CountingSearchService(
                    new \GuzzleHttp\Psr7\Uri($app['config']['search']['v3']['base_url'] . '/offers/'),
                    new Client(new \GuzzleHttp\Client()),
                    $app['config']['search']['v3']['api_key'] ?? null
                );

                return $search->withQueryParameter(
                    'disableDefaultFilters',
                    'true'
                );
            }
        );

        $app[self::ORGANIZERS_COUNTING_SEARCH_SERVICE] = $app->share(
            function ($app) {
                return new \CultuurNet\UDB3\Search\Sapi3CountingSearchService(
                    new \GuzzleHttp\Psr7\Uri($app['config']['search']['v3']['base_url'] . '/organizers/'),
                    new Client(new \GuzzleHttp\Client()),
                    $app['config']['search']['v3']['api_key'] ?? null
                );
            }
        );

        $app['search_results_generator'] = $app->share(
            function (Application $app) {
                $resultsGenerator = new ResultsGenerator(
                    $app[self::SEARCH_SERVICE_OFFERS]
                );
                $resultsGenerator->setLogger($app['search_results_generator_logger']);
                return $resultsGenerator;
            }
        );

        $app['search_results_generator_log_handler'] = $app->share(
            function () {
                return new StreamHandler(__DIR__ . '/../../log/search_results.log');
            }
        );

        $app['search_results_generator_logger'] = $app->share(
            function (Application $app) {
                $logger = new Logger('search-results-generator');
                $logger->pushHandler($app['search_results_generator_log_handler']);
                return $logger;
            }
        );
    }

    public function boot(Application $app)
    {
    }
}

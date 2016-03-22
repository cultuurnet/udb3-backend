<?php

namespace CultuurNet\UDB3\Silex\Search;

use CultuurNet\UDB3\Search\Cache\CacheManager;
use CultuurNet\UDB3\Search\CachedDefaultSearchService;
use CultuurNet\UDB3\Search\PullParsingSearchService;
use CultuurNet\UDB3\Search\ResultsGenerator;
use CultuurNet\UDB3\SearchAPI2\DefaultSearchService as SearchAPI2;
use CultuurNet\UDB3\SearchAPI2\Filters\NotUDB3Place;
use CultuurNet\UDB3\SearchAPI2\FilteredSearchService;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Predis\Client;
use Silex\Application;
use Silex\ServiceProviderInterface;

class SearchServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Application $app)
    {
        $app['search_api_2'] = $app->share(
            function ($app) {
                $searchApiUrl =
                    $app['config']['uitid']['base_url'] .
                    $app['config']['uitid']['apis']['search'];

                return new SearchAPI2(
                    $searchApiUrl,
                    $app['uitid_consumer_credentials']
                );
            }
        );

        $app['filtered_search_api_2'] = $app->share(
            function ($app) {
                $filteredSearchService = new FilteredSearchService(
                    $app['search_api_2']
                );
                $filteredSearchService->filter(new NotUDB3Place());
                return $filteredSearchService;
            }
        );

        $app['search_service'] = $app->share(
            function ($app) {
                /** @var \Qandidate\Toggle\ToggleManager $toggles */
                $toggles = $app['toggles'];

                $includePlaces = $toggles->active(
                    'search-include-places',
                    $app['toggles.context']
                );

                $searchAPI = $includePlaces ? 'search_api_2' : 'filtered_search_api_2';

                return new PullParsingSearchService(
                    $app[$searchAPI],
                    $app['iri_generator'],
                    $app['place_iri_generator']
                );
            }
        );

        $app['cached_search_service'] = $app->share(
            function ($app) {
                return new CachedDefaultSearchService(
                    $app['search_service'],
                    $app['cache']('default_search')
                );
            }
        );

        $app['search_cache_manager'] = $app->share(
            function (Application $app) {
                $parameters = $app['config']['cache']['redis'];

                return new CacheManager(
                    $app['cached_search_service'],
                    new Client($parameters)
                );
            }
        );

        $app['search_cache_manager'] = $app->extend(
            'search_cache_manager',
            function (CacheManager $manager, Application $app) {
                $logger = new Logger('search_cache_manager');
                $logger->pushHandler(
                    new StreamHandler(__DIR__ . '/../../log/search_cache_manager.log')
                );
                $manager->setLogger($logger);

                return $manager;
            }
        );

        $app['search_results_generator'] = $app->share(
            function (Application $app) {
                return new ResultsGenerator(
                    $app['search_service']
                );
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function boot(Application $app)
    {
    }
}

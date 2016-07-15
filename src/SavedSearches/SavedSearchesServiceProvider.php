<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Silex\SavedSearches;

use CultuurNet\UDB3\SavedSearches\CombinedSavedSearchRepository;
use CultuurNet\UDB3\SavedSearches\FixedSavedSearchRepository;
use CultuurNet\UDB3\SavedSearches\UiTIDSavedSearchRepository;
use CultuurNet\UDB3\SavedSearches\SavedSearchesServiceFactory;
use CultuurNet\UDB3\UDB2\Consumer;
use Silex\Application;
use Silex\ServiceProviderInterface;

class SavedSearchesServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritdoc
     */
    public function register(Application $app)
    {
        $app['saved_searches_service_factory'] = $app->share(
            function ($app) {
                $consumer = new Consumer(
                    $app['config']['uitid']['base_url'],
                    $app['culturefeed_consumer_credentials']
                );

                return new SavedSearchesServiceFactory(
                    $consumer
                );
            }
        );

        $app['saved_searches'] = $app->share(
            function (Application $app) {
                /* @var \CultuurNet\UDB3\SavedSearches\SavedSearchesServiceFactory $serviceFactory */
                $serviceFactory = $app['saved_searches_service_factory'];
                $tokenCredentials = $app['culturefeed_token_credentials'];
                return $serviceFactory->withTokenCredentials($tokenCredentials);
            }
        );

        $app['saved_searches_logger'] = $app->share(
            function (Application $app) {
                $logger = new \Monolog\Logger('saved_searches');
                $logger->pushHandler(
                    new \Monolog\Handler\StreamHandler(__DIR__ . '/../log/saved_searches.log')
                );
                return $logger;
            }
        );

        $app['saved_searches_repository'] = $app->share(
            function (Application $app) {
                $uitIDRepository = new UiTIDSavedSearchRepository($app['saved_searches']);
                $uitIDRepository->setLogger($app['saved_searches_logger']);
                $user = $app['current_user'];
                $fixedRepository = new FixedSavedSearchRepository($user);
                $repository = new CombinedSavedSearchRepository(
                    $fixedRepository,
                    $uitIDRepository
                );
                return $repository;
            }
        );
    }

    /**
     * @inheritdoc
     */
    public function boot(Application $app)
    {
    }
}

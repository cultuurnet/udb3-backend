<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Silex;

use CultuurNet\UDB3\SavedSearches\ReadModel\UiTIDSavedSearchRepository;
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
                    $app['uitid_consumer_credentials']
                );

                return new SavedSearchesServiceFactory(
                    $consumer
                );
            }
        );

        $app['saved_searches'] = $app->share(function (Application $app) {
            /** @var \Symfony\Component\HttpFoundation\Session\SessionInterface $session */
            $session = $app['session'];

            /** @var \CultuurNet\Auth\User $minimalUserData */
            $minimalUserData = $session->get('culturefeed_user');

            /* @var \CultuurNet\UDB3\SavedSearches\SavedSearchesServiceFactory $serviceFactory */
            $serviceFactory = $app['saved_searches_service_factory'];

            return $serviceFactory->withTokenCredentials($minimalUserData->getTokenCredentials());
        });

        $app['saved_searches_logger'] = $app->share(function(Application $app) {
            $logger = new \Monolog\Logger('saved_searches');
            $logger->pushHandler(
                new \Monolog\Handler\StreamHandler(__DIR__ . '/../log/saved_searches.log')
            );
            return $logger;
        });

        $app['saved_searches_repository'] = $app->share(function (Application $app) {
            $repository = new UiTIDSavedSearchRepository($app['saved_searches']);
            $repository->setLogger($app['saved_searches_logger']);
            return $repository;
        });
    }

    /**
     * @inheritdoc
     */
    public function boot(Application $app)
    {

    }
}

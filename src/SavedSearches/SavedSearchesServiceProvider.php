<?php

namespace CultuurNet\UDB3\Silex\SavedSearches;

use CultuurNet\UDB3\SavedSearches\CombinedSavedSearchRepository;
use CultuurNet\UDB3\SavedSearches\FixedSavedSearchRepository;
use CultuurNet\UDB3\SavedSearches\ReadModel\SavedSearchRepositoryInterface;
use CultuurNet\UDB3\SavedSearches\UDB3SavedSearchRepository;
use CultuurNet\UDB3\SavedSearches\UiTIDSavedSearchRepository;
use CultuurNet\UDB3\SavedSearches\SavedSearchesServiceFactory;
use CultuurNet\UDB3\SavedSearches\ValueObject\CreatedByQueryMode;
use CultuurNet\UDB3\UDB2\Consumer;
use Silex\Application;
use Silex\ServiceProviderInterface;
use ValueObjects\StringLiteral\StringLiteral;

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
            function () {
                $logger = new \Monolog\Logger('saved_searches');
                $logger->pushHandler(
                    new \Monolog\Handler\StreamHandler(__DIR__ . '/../../log/saved_searches.log')
                );
                return $logger;
            }
        );

        $app['udb3_saved_searches_repo_sapi2'] = $app->share(
            function (Application $app) {
                $user = $app['current_user'];

                return new UDB3SavedSearchRepository(
                    $app['dbal_connection'],
                    new StringLiteral('saved_searches_sapi2'),
                    $app['uuid_generator'],
                    new StringLiteral($user->id)
                );
            }
        );

        $app['saved_searches_repository'] = $app->share(
            function (Application $app) {
                $fixedRepository = $this->createFixedSavedSearchRepo($app);
                $savedSearchesRepo = $this->createSavedSearchesRepo($app);

                $repository = new CombinedSavedSearchRepository(
                    $fixedRepository,
                    $savedSearchesRepo
                );
                return $repository;
            }
        );

        $app['saved_searches_command_handler'] = $app->share(
            function (Application $app) {
                if ($app['config']['saved_searches'] === 'udb3-sapi2') {
                    return new \CultuurNet\UDB3\SavedSearches\UDB3SavedSearchesCommandHandler(
                        $app['udb3_saved_searches_repo_sapi2']
                    );
                } else {
                    return new \CultuurNet\UDB3\SavedSearches\SavedSearchesCommandHandler(
                        $app['saved_searches_service_factory']
                    );
                }
            }
        );
    }

    /**
     * @inheritdoc
     */
    public function boot(Application $app)
    {
    }

    /**
     * @param Application $app
     * @return SavedSearchRepositoryInterface
     */
    private function createFixedSavedSearchRepo(Application $app): SavedSearchRepositoryInterface
    {
        $user = $app['current_user'];

        $createdByQueryMode = CreatedByQueryMode::UUID();
        if (!empty($app['config']['created_by_query_mode'])) {
            $createdByQueryMode = CreatedByQueryMode::fromNative(
                $app['config']['created_by_query_mode']
            );
        }

        return new FixedSavedSearchRepository($user, $createdByQueryMode);
    }

    /**
     * @param Application $app
     * @return SavedSearchRepositoryInterface
     */
    private function createSavedSearchesRepo(Application $app): SavedSearchRepositoryInterface
    {
        if ($app['config']['saved_searches'] === 'udb3-sapi2') {
            $savedSearchesRepo = $app['udb3_saved_searches_repo_sapi2'];
        } else {
            $savedSearchesRepo = new UiTIDSavedSearchRepository($app['saved_searches']);
            $savedSearchesRepo->setLogger($app['saved_searches_logger']);
        }

        return $savedSearchesRepo;
    }
}

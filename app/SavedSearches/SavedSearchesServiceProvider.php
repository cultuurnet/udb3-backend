<?php

namespace CultuurNet\UDB3\Silex\SavedSearches;

use CultuurNet\UDB3\SavedSearches\CombinedSavedSearchRepository;
use CultuurNet\UDB3\SavedSearches\ReadModel\SavedSearchRepositoryInterface;
use CultuurNet\UDB3\SavedSearches\Sapi3FixedSavedSearchRepository;
use CultuurNet\UDB3\SavedSearches\UDB3SavedSearchRepository;
use CultuurNet\UDB3\SavedSearches\ValueObject\CreatedByQueryMode;
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
        $app['udb3_saved_searches_repo_sapi3'] = $app->share(
            function (Application $app) {
                $user = $app['current_user'];

                return new UDB3SavedSearchRepository(
                    $app['dbal_connection'],
                    new StringLiteral('saved_searches_sapi3'),
                    $app['uuid_generator'],
                    new StringLiteral($user->id)
                );
            }
        );

        $app[SavedSearchRepositoryInterface::class] = $app->share(
            function (Application $app) {
                return new CombinedSavedSearchRepository(
                    new Sapi3FixedSavedSearchRepository(
                        $app['current_user'],
                        $this->getCreatedByQueryMode($app)
                    ),
                    $app['udb3_saved_searches_repo_sapi3']
                );
            }
        );

        $app['saved_searches_command_handler'] = $app->share(
            function (Application $app) {
                return new \CultuurNet\UDB3\SavedSearches\UDB3SavedSearchesCommandHandler(
                    $app['udb3_saved_searches_repo_sapi3']
                );
            }
        );
    }

    /**
     * @inheritdoc
     */
    public function boot(Application $app)
    {
    }


    private function getCreatedByQueryMode(Application $app): CreatedByQueryMode
    {
        $createdByQueryMode = CreatedByQueryMode::UUID();
        if (!empty($app['config']['created_by_query_mode'])) {
            $createdByQueryMode = CreatedByQueryMode::fromNative(
                $app['config']['created_by_query_mode']
            );
        }

        return $createdByQueryMode;
    }
}

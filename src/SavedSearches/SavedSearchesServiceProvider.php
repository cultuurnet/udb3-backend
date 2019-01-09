<?php

namespace CultuurNet\UDB3\Silex\SavedSearches;

use CultuurNet\UDB3\SavedSearches\CombinedSavedSearchRepository;
use CultuurNet\UDB3\SavedSearches\FixedSavedSearchRepository;
use CultuurNet\UDB3\SavedSearches\ReadModel\SavedSearchRepositoryInterface;
use CultuurNet\UDB3\SavedSearches\SavedSearchReadRepositoryCollection;
use CultuurNet\UDB3\SavedSearches\SavedSearchWriteRepositoryCollection;
use CultuurNet\UDB3\SavedSearches\UDB3SavedSearchRepository;
use CultuurNet\UDB3\SavedSearches\ValueObject\CreatedByQueryMode;
use CultuurNet\UDB3\ValueObject\SapiVersion;
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

        $app['saved_searches_read_collection'] = $app->share(
            function (Application $app) {
                $fixedRepository = $this->createFixedSavedSearchRepo($app);
                $savedSearchReadRepositoryCollection = new SavedSearchReadRepositoryCollection();

                $savedSearchReadRepositoryCollection = $savedSearchReadRepositoryCollection
                    ->withRepository(
                        SapiVersion::V3(),
                        new CombinedSavedSearchRepository(
                            $fixedRepository,
                            $app['udb3_saved_searches_repo_sapi3']
                        )
                    )
                    ->withRepository(
                        SapiVersion::V2(),
                        new CombinedSavedSearchRepository(
                            $fixedRepository,
                            $app['udb3_saved_searches_repo_sapi2']
                        )
                    );

                return $savedSearchReadRepositoryCollection;
            }
        );

        $app['saved_searches_command_handler'] = $app->share(
            function (Application $app) {
                $savedSearchWriteRepositoryCollection = new SavedSearchWriteRepositoryCollection();

                $savedSearchWriteRepositoryCollection = $savedSearchWriteRepositoryCollection
                    ->withRepository(
                        SapiVersion::V3(),
                        $app['udb3_saved_searches_repo_sapi3']
                    )
                    ->withRepository(
                        SapiVersion::V2(),
                        $app['udb3_saved_searches_repo_sapi2']
                    );

                return new \CultuurNet\UDB3\SavedSearches\UDB3SavedSearchesCommandHandler(
                    $savedSearchWriteRepositoryCollection
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
}

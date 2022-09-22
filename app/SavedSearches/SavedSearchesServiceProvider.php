<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\SavedSearches;

use CultuurNet\UDB3\Http\Auth\Jwt\JsonWebToken;
use CultuurNet\UDB3\Http\SavedSearches\CreateSavedSearchRequestHandler;
use CultuurNet\UDB3\Http\SavedSearches\DeleteSavedSearchRequestHandler;
use CultuurNet\UDB3\Http\SavedSearches\ReadSavedSearchesRequestHandler;
use CultuurNet\UDB3\SavedSearches\CombinedSavedSearchRepository;
use CultuurNet\UDB3\SavedSearches\ReadModel\SavedSearchRepositoryInterface;
use CultuurNet\UDB3\SavedSearches\Sapi3FixedSavedSearchRepository;
use CultuurNet\UDB3\SavedSearches\UDB3SavedSearchesCommandHandler;
use CultuurNet\UDB3\SavedSearches\UDB3SavedSearchRepository;
use CultuurNet\UDB3\SavedSearches\ValueObject\CreatedByQueryMode;
use CultuurNet\UDB3\User\Auth0UserIdentityResolver;
use CultuurNet\UDB3\User\CurrentUser;
use Silex\Application;
use Silex\ServiceProviderInterface;
use CultuurNet\UDB3\StringLiteral;

class SavedSearchesServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app): void
    {
        $app['udb3_saved_searches_repo_sapi3'] = $app->share(
            function (Application $app) {
                return new UDB3SavedSearchRepository(
                    $app['dbal_connection'],
                    new StringLiteral('saved_searches_sapi3'),
                    $app['uuid_generator'],
                    new StringLiteral($app[CurrentUser::class]->getId())
                );
            }
        );

        $app[SavedSearchRepositoryInterface::class] = $app->share(
            function (Application $app) {
                return new CombinedSavedSearchRepository(
                    new Sapi3FixedSavedSearchRepository(
                        $app[JsonWebToken::class],
                        $app[Auth0UserIdentityResolver::class],
                        $this->getCreatedByQueryMode($app)
                    ),
                    $app['udb3_saved_searches_repo_sapi3']
                );
            }
        );

        $app['saved_searches_command_handler'] = $app->share(
            function (Application $app) {
                return new UDB3SavedSearchesCommandHandler(
                    $app['udb3_saved_searches_repo_sapi3']
                );
            }
        );

        $app[ReadSavedSearchesRequestHandler::class] = $app->share(
            function (Application $app) {
                return new ReadSavedSearchesRequestHandler(
                    $app[SavedSearchRepositoryInterface::class]
                );
            }
        );

        $app[CreateSavedSearchRequestHandler::class] = $app->share(
            function (Application $app) {
                return new CreateSavedSearchRequestHandler(
                    $app[CurrentUser::class]->getId(),
                    $app['event_command_bus']
                );
            }
        );

        $app[DeleteSavedSearchRequestHandler::class] = $app->share(
            function (Application $app) {
                return new DeleteSavedSearchRequestHandler(
                    $app[CurrentUser::class]->getId(),
                    $app['event_command_bus']
                );
            }
        );
    }

    public function boot(Application $app): void
    {
    }

    private function getCreatedByQueryMode(Application $app): CreatedByQueryMode
    {
        $createdByQueryMode = CreatedByQueryMode::uuid();
        if (!empty($app['config']['created_by_query_mode'])) {
            $createdByQueryMode = new CreatedByQueryMode(
                $app['config']['created_by_query_mode']
            );
        }

        return $createdByQueryMode;
    }
}

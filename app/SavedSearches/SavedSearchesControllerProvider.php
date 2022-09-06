<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\SavedSearches;

use CultuurNet\UDB3\Http\SavedSearches\DeleteSavedSearchRequestHandler;
use CultuurNet\UDB3\Http\SavedSearches\CreateSavedSearchRequestHandler;
use CultuurNet\UDB3\Http\SavedSearches\ReadSavedSearchesRequestHandler;
use CultuurNet\UDB3\SavedSearches\ReadModel\SavedSearchRepositoryInterface;
use CultuurNet\UDB3\User\CurrentUser;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;

class SavedSearchesControllerProvider implements ControllerProviderInterface
{
    /**
     * @inheritdoc
     */
    public function connect(Application $app)
    {
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

        /* @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->get('/v3/', ReadSavedSearchesRequestHandler::class);

        $controllers->post('/v3/', CreateSavedSearchRequestHandler::class);
        $controllers->delete('/v3/{id}/', DeleteSavedSearchRequestHandler::class);

        return $controllers;
    }
}

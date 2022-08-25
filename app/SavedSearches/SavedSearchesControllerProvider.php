<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\SavedSearches;

use CultuurNet\UDB3\Http\SavedSearches\DeleteSavedSearchesRequestHandler;
use CultuurNet\UDB3\Http\SavedSearches\EditSavedSearchesRestController;
use CultuurNet\UDB3\Http\SavedSearches\ReadSavedSearchesController;
use CultuurNet\UDB3\Http\SavedSearches\SaveSavedSearchesRequestHandler;
use CultuurNet\UDB3\SavedSearches\ReadModel\SavedSearchRepositoryInterface;
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
        $app['saved_searches_read_controller'] = $app->share(
            function (Application $app) {
                return new ReadSavedSearchesController(
                    $app[SavedSearchRepositoryInterface::class]
                );
            }
        );

        $app[SaveSavedSearchesRequestHandler::class] = $app->share(
            function (Application $app) {
                return new SaveSavedSearchesRequestHandler(
                    $app['current_user_id'],
                    $app['event_command_bus']
                );
            }
        );

        $app[DeleteSavedSearchesRequestHandler::class] = $app->share(
            function (Application $app) {
                return new DeleteSavedSearchesRequestHandler(
                    $app['current_user_id'],
                    $app['event_command_bus']
                );
            }
        );

        /* @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->get('/v3/', 'saved_searches_read_controller:ownedByCurrentUser');

        $controllers->post('/v3/', SaveSavedSearchesRequestHandler::class);
        $controllers->delete('/v3/{id}/', DeleteSavedSearchesRequestHandler::class);

        return $controllers;
    }
}

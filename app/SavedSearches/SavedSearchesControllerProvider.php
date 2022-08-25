<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\SavedSearches;

use CultuurNet\UDB3\Http\SavedSearches\DeleteSavedSearchesRequestHandler;
use CultuurNet\UDB3\Http\SavedSearches\ReadSavedSearchesRequestHandler;
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
        $app[ReadSavedSearchesRequestHandler::class] = $app->share(
            function (Application $app) {
                return new ReadSavedSearchesRequestHandler(
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

        $controllers->get('/v3/', ReadSavedSearchesRequestHandler::class);

        $controllers->post('/v3/', SaveSavedSearchesRequestHandler::class);
        $controllers->delete('/v3/{id}/', DeleteSavedSearchesRequestHandler::class);

        return $controllers;
    }
}

<?php

namespace CultuurNet\UDB3\Silex\SavedSearches;

use CultuurNet\UDB3\Symfony\SavedSearches\SavedSearchesRestController;
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
        $app['saved_searches_controller'] = $app->share(
            function (Application $app) {
                return new SavedSearchesRestController(
                    $app['saved_searches_repository'],
                    $app['current_user'],
                    $app['event_command_bus']
                );
            }
        );

        /* @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->get('/', 'saved_searches_controller:ownedByCurrentUser');
        $controllers->post('/', 'saved_searches_controller:save');

        $controllers->delete('/{id}', 'saved_searches_controller:delete');

        return $controllers;
    }
}

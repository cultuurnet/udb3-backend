<?php

namespace CultuurNet\UDB3\Silex\SavedSearches;

use CultuurNet\UDB3\Symfony\SavedSearches\EditSavedSearchesRestController;
use CultuurNet\UDB3\Symfony\SavedSearches\ReadSavedSearchesController;
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
                    $app['current_user'],
                    $app['saved_searches_repository']
                );
            }
        );

        $app['saved_searches_edit_controller'] = $app->share(
            function (Application $app) {
                return new EditSavedSearchesRestController(
                    $app['current_user'],
                    $app['event_command_bus']
                );
            }
        );

        /* @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->get('/', 'saved_searches_read_controller:ownedByCurrentUser');

        $controllers->post('/', 'saved_searches_edit_controller:save');
        $controllers->delete('/{sapiVersion}/{id}', 'saved_searches_edit_controller:delete');

        return $controllers;
    }
}

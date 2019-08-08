<?php

namespace CultuurNet\UDB3\Silex\SavedSearches;

use CultuurNet\UDB3\Http\SavedSearches\EditSavedSearchesRestController;
use CultuurNet\UDB3\Http\SavedSearches\ReadSavedSearchesController;
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
                    $app['saved_searches_read_collection']
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

        $controllers->get('/{sapiVersion}', 'saved_searches_read_controller:ownedByCurrentUser');

        $controllers->post('/{sapiVersion}', 'saved_searches_edit_controller:save');
        $controllers->delete('/{sapiVersion}/{id}', 'saved_searches_edit_controller:delete');

        return $controllers;
    }
}

<?php

namespace CultuurNet\UDB3\Silex\SavedSearches;

use CultuurNet\UDB3\SavedSearches\Command\SavedSearchCommand;
use CultuurNet\UDB3\SavedSearches\Command\SubscribeToSavedSearchJSONDeserializer;
use CultuurNet\UDB3\SavedSearches\Command\UnsubscribeFromSavedSearch;
use CultuurNet\UDB3\SavedSearches\ReadModel\SavedSearchRepositoryInterface;
use CultuurNet\UDB3\Symfony\CommandDeserializerController;
use CultuurNet\UDB3\Symfony\SavedSearches\SavedSearchesRestController;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use ValueObjects\String\String;

class SavedSearchesControllerProvider implements ControllerProviderInterface
{
    /**
     * Returns routes to connect to the given application.
     *
     * @param Application $app An Application instance
     *
     * @return ControllerCollection A ControllerCollection instance
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

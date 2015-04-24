<?php

namespace CultuurNet\UDB3\Silex;

use CultuurNet\UDB3\SavedSearches\Command\SubscribeToSavedSearch;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

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
        // Creates a new controller based on the default route
        $controllers = $app['controllers_factory'];

        $controllers->post(
            '/subscribe',
            function (Request $request, Application $app) {
                /* @var \CultureFeed_User $user */
                $user = $app['current_user'];

                $name = $request->request->get('name');
                $query = $request->request->get('query');

                $command = new SubscribeToSavedSearch($user->id, $name, $query);

                /** @var \Broadway\CommandHandling\CommandBusInterface $commandBus */
                $commandBus = $app['event_command_bus'];
                $commandId = $commandBus->dispatch($command);

                return JsonResponse::create(
                    ['commandId' => $commandId]
                );
            }
        );
    }
}

<?php

namespace CultuurNet\UDB3\Silex;

use CultuurNet\UDB3\SavedSearches\Command\SavedSearchCommand;
use CultuurNet\UDB3\SavedSearches\Command\SubscribeToSavedSearchJSONDeserializer;
use CultuurNet\UDB3\SavedSearches\Command\UnsubscribeFromSavedSearch;
use CultuurNet\UDB3\SavedSearches\ReadModel\SavedSearchRepositoryInterface;
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
        // Creates a new controller based on the default route.
        /* @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];
        $controllerProvider = $this;

        $controllers->post(
            '/',
            function (Request $request, Application $app) use ($controllerProvider) {
                $userId = $controllerProvider->getUserIDFromApp($app);
                $deserializer = new SubscribeToSavedSearchJSONDeserializer($userId);
                $data = new String($request->getContent());

                $command = $deserializer->deserialize($data);
                $commandId = $controllerProvider->dispatchEventCommand($command, $app);

                return $controllerProvider->getResponseForCommandId($commandId);
            }
        );

        $controllers->delete(
            '/{id}',
            function (Application $app, $id) use ($controllerProvider) {
                $userId = $controllerProvider->getUserIDFromApp($app);
                $searchId = new String($id);

                $command = new UnsubscribeFromSavedSearch($userId, $searchId);
                $commandId = $controllerProvider->dispatchEventCommand($command, $app);

                return $controllerProvider->getResponseForCommandId($commandId);
            }
        );

        $controllers->get(
            '/',
            function (Application $app) {
                /** @var SavedSearchRepositoryInterface $savedSearches */
                $savedSearches = $app['saved_searches_repository'];

                return JsonResponse::create($savedSearches->ownedByCurrentUser());
            }
        );

        return $controllers;
    }

    /**
     * @param Application $app
     * @return String
     */
    private function getUserIDFromApp(Application $app)
    {
        /* @var \CultureFeed_User $user */
        $user = $app['current_user'];
        return new String($user->id);
    }

    /**
     * @param SavedSearchCommand $command
     * @param Application $app
     * @return String
     */
    private function dispatchEventCommand(SavedSearchCommand $command, Application $app)
    {
        /** @var \Broadway\CommandHandling\CommandBusInterface $commandBus */
        $commandBus = $app['event_command_bus'];
        $commandId = $commandBus->dispatch($command);
        return new String($commandId);
    }

    /**
     * @param String $commandId
     */
    private function getResponseForCommandId(String $commandId) {
        return JsonResponse::create(
            ['commandId' => (string) $commandId]
        );
    }
}

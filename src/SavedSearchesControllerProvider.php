<?php

namespace CultuurNet\UDB3\Silex;

use CultuurNet\UDB3\SavedSearches\Command\SubscribeToSavedSearchJSONDeserializer;
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
        // Creates a new controller based on the default route
        $controllers = $app['controllers_factory'];

        $controllers->post(
            '/',
            function (Request $request, Application $app) {
                /* @var \CultureFeed_User $user */
                $user = $app['current_user'];
                $userId = new String($user->id);
                $requestContent = new String($request->getContent());

                $deserializer = new SubscribeToSavedSearchJSONDeserializer($userId);
                $command = $deserializer->deserialize($requestContent);

                /** @var \Broadway\CommandHandling\CommandBusInterface $commandBus */
                $commandBus = $app['event_command_bus'];
                $commandId = $commandBus->dispatch($command);

                return JsonResponse::create(
                    ['commandId' => $commandId]
                );
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
}

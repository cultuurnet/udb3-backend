<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\UiTPASService;

use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\UiTPASService\Controller\AddCardSystemToEventRequestHandler;
use CultuurNet\UDB3\UiTPASService\Controller\DeleteCardSystemFromEventRequestHandler;
use CultuurNet\UDB3\UiTPASService\Controller\GetCardSystemsFromEventRequestHandler;
use CultuurNet\UDB3\UiTPASService\Controller\GetUiTPASDetailRequestHandler;
use CultuurNet\UDB3\UiTPASService\Controller\SetCardSystemsOnEventRequestHandler;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;

class UiTPASServiceEventControllerProvider implements ControllerProviderInterface
{
    public function connect(Application $app): ControllerCollection
    {
        $app[GetUiTPASDetailRequestHandler::class] = $app->share(
            fn (Application $app) => new GetUiTPASDetailRequestHandler(
                $app['uitpas'],
                new CallableIriGenerator(
                    fn (string $eventId) => $app['config']['url'] . '/uitpas/events' . $eventId
                ),
                new CallableIriGenerator(
                    fn (string $eventId) => $app['config']['url'] . '/uitpas/events' . $eventId . '/card-systems'
                )
            )
        );

        $app[GetCardSystemsFromEventRequestHandler::class] = $app->share(
            fn (Application $app) => new GetCardSystemsFromEventRequestHandler($app['uitpas'])
        );

        $app[SetCardSystemsOnEventRequestHandler::class] = $app->share(
            fn (Application $app) => new SetCardSystemsOnEventRequestHandler($app['uitpas'])
        );

        $app[AddCardSystemToEventRequestHandler::class] = $app->share(
            fn (Application $app) => new AddCardSystemToEventRequestHandler($app['uitpas'])
        );

        $app[DeleteCardSystemFromEventRequestHandler::class] = $app->share(
            fn (Application $app) => new DeleteCardSystemFromEventRequestHandler($app['uitpas'])
        );

        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->get('/{eventId}/', GetUiTPASDetailRequestHandler::class);

        $controllers->get('/{eventId}/card-systems/', GetCardSystemsFromEventRequestHandler::class);

        $controllers->put('/{eventId}/card-systems/', SetCardSystemsOnEventRequestHandler::class);

        $controllers->put('/{eventId}/card-systems/{cardSystemId}/', AddCardSystemToEventRequestHandler::class);

        $controllers->put(
            '/{eventId}/card-systems/{cardSystemId}/distribution-key/{distributionKeyId}/',
            AddCardSystemToEventRequestHandler::class
        );

        $controllers->delete('/{eventId}/card-systems/{cardSystemId}/', DeleteCardSystemFromEventRequestHandler::class);

        return $controllers;
    }
}

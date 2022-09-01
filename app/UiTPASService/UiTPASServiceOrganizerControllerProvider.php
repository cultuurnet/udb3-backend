<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\UiTPASService;

use CultuurNet\UDB3\UiTPASService\Controller\GetCardSystemsFromEventRequestHandler;
use CultuurNet\UDB3\UiTPASService\Controller\GetCardSystemsFromOrganizerRequestHandler;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;

class UiTPASServiceOrganizerControllerProvider implements ControllerProviderInterface
{
    public function connect(Application $app): ControllerCollection
    {
        $app[GetCardSystemsFromEventRequestHandler::class] = $app->share(
            fn (Application $app) => new GetCardSystemsFromEventRequestHandler($app['uitpas'])
        );

        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->get('/{organizerId}/card-systems/', GetCardSystemsFromOrganizerRequestHandler::class);

        return $controllers;
    }
}

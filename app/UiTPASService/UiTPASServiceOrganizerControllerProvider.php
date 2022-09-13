<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\UiTPASService;

use CultuurNet\UDB3\UiTPASService\Controller\GetCardSystemsFromOrganizerRequestHandler;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

class UiTPASServiceOrganizerControllerProvider implements ControllerProviderInterface, ServiceProviderInterface
{
    public function connect(Application $app): ControllerCollection
    {
        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->get('/{organizerId}/card-systems/', GetCardSystemsFromOrganizerRequestHandler::class);

        return $controllers;
    }

    public function register(Application $app): void
    {
        $app[GetCardSystemsFromOrganizerRequestHandler::class] = $app->share(
            fn (Application $app) => new GetCardSystemsFromOrganizerRequestHandler($app['uitpas'])
        );
    }

    public function boot(Application $app): void
    {
    }
}

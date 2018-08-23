<?php

namespace CultuurNet\UDB3\Silex\MyOrganizers;

use CultuurNet\UDB3\Symfony\MyOrganizers\MyOrganizersController;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;

class MyOrganizersControllerProvider implements ControllerProviderInterface
{
    private const ROUTE_NAME = 'user-organizers';

    public function connect(Application $app)
    {
        $app['my_organizers_controller'] = $app->share(
            function (Application $app) {
                $controller = new MyOrganizersController(
                    self::ROUTE_NAME,
                    $app['current_user'],
                    $app['url_generator'],
                    $app[MyOrganizersServiceProvider::LOOKUP]
                );

                return $controller;
            }
        );

        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers
            ->get('user/organizers/', 'my_organizers_controller:itemsOwnedByCurrentUser')
            ->bind(self::ROUTE_NAME);

        return $controllers;
    }

}
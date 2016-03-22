<?php

namespace CultuurNet\UDB3\Silex\Dashboard;

use CultuurNet\UDB3\Symfony\Dashboard\DashboardRestController;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;

class DashboardControllerProvider implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['dashboard_controller'] = $app->share(
            function (Application $app) {
                return new DashboardRestController(
                    $app['index.repository'],
                    $app['current_user'],
                    $app['url_generator']
                );
            }
        );

        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers
            ->get('items', 'dashboard_controller:itemsOwnedByCurrentUser')
            ->bind('dashboard-items');

        return $controllers;
    }
}

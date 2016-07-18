<?php

namespace CultuurNet\UDB3\Silex\Role;

use CultuurNet\UDB3\Symfony\Role\EditRoleRestController;
use CultuurNet\UDB3\Symfony\Role\ReadRoleRestController;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;

class RoleControllerProvider implements ControllerProviderInterface
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
        $app['role_controller'] = $app->share(
            function (Application $app) {
                return new ReadRoleRestController(
                    $app['role_service'],
                    $app['role_reading_service'],
                    $app['current_user'],
                    $app['config']['user_permissions']
                );
            }
        );

        $app['role_edit_controller'] = $app->share(
            function (Application $app) {
                return new EditRoleRestController(
                    $app['role_editing_service'],
                    $app['event_command_bus']
                );
            }
        );

        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers
            ->get('/roles/{id}', 'role_controller:get')
            ->bind('role');

        $controllers->post(
            '/roles/',
            'role_edit_controller:create'
        );

        $controllers
            ->get('/permissions/', 'role_controller:getPermissions');

        $controllers
            ->get('/user/permissions/', 'role_controller:getUserPermissions');

        //$controllers->delete('/roles/{cdbid}', 'role_edit_controller:delete');

        return $controllers;
    }
}

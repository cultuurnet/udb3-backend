<?php

namespace CultuurNet\UDB3\Silex\Role;

use CultuurNet\UDB3\Role\Commands\UpdateRoleRequestDeserializer;
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
                    $app['event_command_bus'],
                    new UpdateRoleRequestDeserializer()
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
        $controllers->patch(
            '/roles/{id}',
            'role_edit_controller:update'
        )->bind('role');

        $controllers
            ->get('/permissions/', 'role_controller:getPermissions');

        $controllers
            ->get('/user/permissions/', 'role_controller:getUserPermissions');

        $controllers->delete('/roles/{roleId}', 'role_edit_controller:delete');

        $controllers->get(
            '/roles/{id}/permissions/',
            'role_controller:getRolePermissions'
        );

        $controllers->put(
            '/roles/{roleId}/permissions/{permissionKey}',
            'role_edit_controller:addPermission'
        );

        $controllers->delete(
            '/roles/{roleId}/permissions/{permissionKey}',
            'role_edit_controller:removePermission'
        );

        return $controllers;
    }
}

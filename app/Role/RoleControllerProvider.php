<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Role;

use CultuurNet\UDB3\Role\Commands\UpdateRoleRequestDeserializer;
use CultuurNet\UDB3\Silex\Labels\LabelServiceProvider;
use CultuurNet\UDB3\Http\Deserializer\Role\QueryJSONDeserializer;
use CultuurNet\UDB3\Http\Role\EditRoleRestController;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;

class RoleControllerProvider implements ControllerProviderInterface
{
    public function connect(Application $app): ControllerCollection
    {
        $app['role_edit_controller'] = $app->share(
            function (Application $app) {
                return new EditRoleRestController(
                    $app['role_editing_service'],
                    $app['event_command_bus'],
                    new UpdateRoleRequestDeserializer(),
                    $app[LabelServiceProvider::JSON_READ_REPOSITORY],
                    new QueryJSONDeserializer()
                );
            }
        );

        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->post(
            '/roles/',
            'role_edit_controller:create'
        );

        $controllers->patch(
            '/roles/{id}/',
            'role_edit_controller:update'
        );

        $controllers->post(
            '/roles/{id}/constraints/',
            'role_edit_controller:addConstraint'
        );

        $controllers->put(
            '/roles/{id}/constraints/',
            'role_edit_controller:updateConstraint'
        );

        $controllers->delete(
            '/roles/{id}/constraints/',
            'role_edit_controller:removeConstraint'
        );

        $controllers->delete('/roles/{id}/', 'role_edit_controller:delete');

        $controllers->put(
            '/roles/{roleId}/permissions/{permissionKey}/',
            'role_edit_controller:addPermission'
        );

        $controllers->delete(
            '/roles/{roleId}/permissions/{permissionKey}/',
            'role_edit_controller:removePermission'
        );

        $controllers->put(
            '/roles/{roleId}/labels/{labelIdentifier}/',
            'role_edit_controller:addLabel'
        );

        $controllers->delete(
            '/roles/{roleId}/labels/{labelIdentifier}/',
            'role_edit_controller:removeLabel'
        );

        $controllers->put(
            '/roles/{roleId}/users/{userId}/',
            'role_edit_controller:addUser'
        );

        $controllers->delete(
            '/roles/{roleId}/users/{userId}/',
            'role_edit_controller:removeUser'
        );

        return $controllers;
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Role;

use CultuurNet\UDB3\Http\Role\GetPermissionsRequestHandler;
use CultuurNet\UDB3\Http\Role\GetRoleLabelsRequestHandler;
use CultuurNet\UDB3\Http\Role\GetRoleRequestHandler;
use CultuurNet\UDB3\Http\Role\GetRolesFromCurrentUserRequestHandler;
use CultuurNet\UDB3\Http\Role\GetRolesFromUserRequestHandler;
use CultuurNet\UDB3\Http\Role\GetUserPermissionsRequestHandler;
use CultuurNet\UDB3\Http\Role\GetUsersWithRoleRequestHandler;
use CultuurNet\UDB3\Role\Commands\UpdateRoleRequestDeserializer;
use CultuurNet\UDB3\Silex\Labels\LabelServiceProvider;
use CultuurNet\UDB3\Http\Deserializer\Role\QueryJSONDeserializer;
use CultuurNet\UDB3\Http\Role\EditRoleRestController;
use CultuurNet\UDB3\Http\Role\ReadRoleRestController;
use CultuurNet\UDB3\User\CurrentUser;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;

class RoleControllerProvider implements ControllerProviderInterface
{
    public function connect(Application $app): ControllerCollection
    {
        $app['role_controller'] = $app->share(
            function (Application $app) {
                return new ReadRoleRestController(
                    $app['role_service'],
                    $app['role_reading_service'],
                    $app[CurrentUser::class]->getId(),
                    $app[CurrentUser::class]->isGodUser(),
                    $app['role_search_v3_repository'],
                    $app[UserPermissionsServiceProvider::USER_PERMISSIONS_READ_REPOSITORY]
                );
            }
        );

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

        $app[GetRoleRequestHandler::class] = $app->share(
            fn (Application $app) => new GetRoleRequestHandler($app['role_read_repository'])
        );

        $app[GetUsersWithRoleRequestHandler::class] = $app->share(
            fn (Application $app) => new GetUsersWithRoleRequestHandler($app['role_users_read_repository'])
        );

        $app[GetRolesFromUserRequestHandler::class] = $app->share(
            fn (Application $app) => new GetRolesFromUserRequestHandler($app['user_roles_repository'])
        );

        $app[GetRolesFromCurrentUserRequestHandler::class] = $app->share(
            fn (Application $app) => new GetRolesFromCurrentUserRequestHandler($app['user_roles_repository'], $app[CurrentUser::class]->getId())
        );

        $app[GetPermissionsRequestHandler::class] = $app->share(
            fn (Application $app) => new GetPermissionsRequestHandler()
        );

        $app[GetUserPermissionsRequestHandler::class] = $app->share(
            fn (Application $app) => new GetUserPermissionsRequestHandler(
                $app[UserPermissionsServiceProvider::USER_PERMISSIONS_READ_REPOSITORY],
                $app[CurrentUser::class]->getId(),
                $app[CurrentUser::class]->isGodUser(),
            )
        );

        $app[GetRoleLabelsRequestHandler::class] = $app->share(
            fn (Application $app) => new GetRoleLabelsRequestHandler($app['role_labels_read_repository'])
        );

        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers
            ->get('/roles/', 'role_controller:search');

        $controllers->post(
            '/roles/',
            'role_edit_controller:create'
        );

        $controllers->get('/roles/{roleId}/', GetRoleRequestHandler::class);

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

        $controllers->get('/permissions/', GetPermissionsRequestHandler::class);

        $controllers->get('/user/permissions/', GetUserPermissionsRequestHandler::class);

        $controllers->get('/roles/{roleId}/users/', GetUsersWithRoleRequestHandler::class);

        $controllers->put(
            '/roles/{roleId}/permissions/{permissionKey}/',
            'role_edit_controller:addPermission'
        );

        $controllers->delete(
            '/roles/{roleId}/permissions/{permissionKey}/',
            'role_edit_controller:removePermission'
        );

        $controllers->get('/roles/{roleId}/labels/', GetRoleLabelsRequestHandler::class);

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

        $controllers->get('/users/{userId}/roles/', GetRolesFromUserRequestHandler::class);

        $controllers->get('/user/roles/', GetRolesFromCurrentUserRequestHandler::class);

        return $controllers;
    }
}

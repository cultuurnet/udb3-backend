<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Role;

use CultuurNet\UDB3\Silex\Labels\LabelServiceProvider;
use Broadway\UuidGenerator\Rfc4122\Version4Generator;
use CultuurNet\UDB3\Http\Role\AddConstraintRequestHandler;
use CultuurNet\UDB3\Http\Role\AddLabelToRoleRequestHandler;
use CultuurNet\UDB3\Http\Role\AddPermissionToRoleRequestHandler;
use CultuurNet\UDB3\Http\Role\AddRoleToUserRequestHandler;
use CultuurNet\UDB3\Http\Role\CreateRoleRequestHandler;
use CultuurNet\UDB3\Http\Role\DeleteConstraintRequestHandler;
use CultuurNet\UDB3\Http\Role\DeleteRoleRequestHandler;
use CultuurNet\UDB3\Http\Role\GetPermissionsRequestHandler;
use CultuurNet\UDB3\Http\Role\GetRoleLabelsRequestHandler;
use CultuurNet\UDB3\Http\Role\GetRoleRequestHandler;
use CultuurNet\UDB3\Http\Role\GetRolesFromCurrentUserRequestHandler;
use CultuurNet\UDB3\Http\Role\GetRolesFromUserRequestHandler;
use CultuurNet\UDB3\Http\Role\GetUserPermissionsRequestHandler;
use CultuurNet\UDB3\Http\Role\GetUsersWithRoleRequestHandler;
use CultuurNet\UDB3\Http\Role\RemoveLabelFromRoleRequestHandler;
use CultuurNet\UDB3\Http\Role\RemovePermissionFromRoleRequestHandler;
use CultuurNet\UDB3\Http\Role\RemoveRoleFromUserRequestHandler;
use CultuurNet\UDB3\Http\Role\RolesSearchRequestHandler;
use CultuurNet\UDB3\Http\Role\UpdateConstraintRequestHandler;
use CultuurNet\UDB3\Http\Role\UpdateRoleRequestHandler;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;

class RoleControllerProvider implements ControllerProviderInterface
{
    public function connect(Application $app): ControllerCollection
    {
        $app[CreateRoleRequestHandler::class] = $app->share(
            fn (Application $app) => new CreateRoleRequestHandler($app['event_command_bus'], new Version4Generator())
        );

        $app[UpdateRoleRequestHandler::class] = $app->share(
            fn (Application $app) => new UpdateRoleRequestHandler($app['event_command_bus'])
        );

        $app[DeleteRoleRequestHandler::class] = $app->share(
            fn (Application $app) => new DeleteRoleRequestHandler($app['event_command_bus'])
        );

        $app[AddConstraintRequestHandler::class] = $app->share(
            fn (Application $app) => new AddConstraintRequestHandler($app['event_command_bus'])
        );

        $app[UpdateConstraintRequestHandler::class] = $app->share(
            fn (Application $app) => new UpdateConstraintRequestHandler($app['event_command_bus'])
        );

        $app[DeleteConstraintRequestHandler::class] = $app->share(
            fn (Application $app) => new DeleteConstraintRequestHandler($app['event_command_bus'])
        );

        $app[AddPermissionToRoleRequestHandler::class] = $app->share(
            fn (Application $app) => new AddPermissionToRoleRequestHandler($app['event_command_bus'])
        );

        $app[RemovePermissionFromRoleRequestHandler::class] = $app->share(
            fn (Application $app) => new RemovePermissionFromRoleRequestHandler($app['event_command_bus'])
        );

        $app[AddLabelToRoleRequestHandler::class] = $app->share(
            fn (Application $app) => new AddLabelToRoleRequestHandler($app['event_command_bus'], $app[LabelServiceProvider::JSON_READ_REPOSITORY])
        );

        $app[RemoveLabelFromRoleRequestHandler::class] = $app->share(
            fn (Application $app) => new RemoveLabelFromRoleRequestHandler($app['event_command_bus'], $app[LabelServiceProvider::JSON_READ_REPOSITORY])
        );

        $app[AddRoleToUserRequestHandler::class] = $app->share(
            fn (Application $app) => new AddRoleToUserRequestHandler($app['event_command_bus'])
        );

        $app[RemoveRoleFromUserRequestHandler::class] = $app->share(
            fn (Application $app) => new RemoveRoleFromUserRequestHandler($app['event_command_bus'])
        );

        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers->post('/roles/', CreateRoleRequestHandler::class);

        $controllers->patch('/roles/{roleId}/', UpdateRoleRequestHandler::class);

        $controllers->post('/roles/{id}/constraints/', AddConstraintRequestHandler::class);

        $controllers->put('/roles/{id}/constraints/', UpdateConstraintRequestHandler::class);

        $controllers->delete('/roles/{roleId}/constraints/', DeleteConstraintRequestHandler::class);

        $controllers->delete('/roles/{roleId}/', DeleteRoleRequestHandler::class);

        $controllers->put('/roles/{roleId}/permissions/{permissionKey}/', AddPermissionToRoleRequestHandler::class);

        $controllers->delete('/roles/{roleId}/permissions/{permissionKey}/', RemovePermissionFromRoleRequestHandler::class);

        $controllers->put('/roles/{roleId}/labels/{labelId}/', AddLabelToRoleRequestHandler::class);

        $controllers->delete('/roles/{roleId}/labels/{labelId}/', RemoveLabelFromRoleRequestHandler::class);

        $controllers->put('/roles/{roleId}/users/{userId}/', AddRoleToUserRequestHandler::class);

        $controllers->delete('/roles/{roleId}/users/{userId}/', RemoveRoleFromUserRequestHandler::class);

        return $controllers;
    }
}

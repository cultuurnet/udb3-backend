<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Role;

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
use CultuurNet\UDB3\Silex\Labels\LabelServiceProvider;
use CultuurNet\UDB3\User\CurrentUser;
use Silex\Application;
use Silex\ServiceProviderInterface;

final class RoleRequestHandlerServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app): void
    {
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

        $app[RolesSearchRequestHandler::class] = $app->share(
            fn (Application $app) => new RolesSearchRequestHandler($app['role_search_v3_repository'])
        );

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
    }

    public function boot(Application $app): void
    {
    }
}

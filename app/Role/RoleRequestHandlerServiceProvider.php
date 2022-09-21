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
use CultuurNet\UDB3\Http\Role\RolesSearchRequestHandler;
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
    }

    public function boot(Application $app): void
    {
    }
}

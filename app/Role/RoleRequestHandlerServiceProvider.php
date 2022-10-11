<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role;

use Broadway\UuidGenerator\Rfc4122\Version4Generator;
use CultuurNet\UDB3\Container\AbstractServiceProvider;
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
use CultuurNet\UDB3\Labels\LabelServiceProvider;
use CultuurNet\UDB3\User\CurrentUser;

final class RoleRequestHandlerServiceProvider extends AbstractServiceProvider
{
    protected function getProvidedServiceNames(): array
    {
        return [
            GetRoleRequestHandler::class,
            GetUsersWithRoleRequestHandler::class,
            GetRolesFromUserRequestHandler::class,
            GetRolesFromCurrentUserRequestHandler::class,
            GetPermissionsRequestHandler::class,
            GetUserPermissionsRequestHandler::class,
            GetRoleLabelsRequestHandler::class,
            RolesSearchRequestHandler::class,
            CreateRoleRequestHandler::class,
            UpdateRoleRequestHandler::class,
            DeleteRoleRequestHandler::class,
            AddConstraintRequestHandler::class,
            UpdateConstraintRequestHandler::class,
            DeleteConstraintRequestHandler::class,
            AddPermissionToRoleRequestHandler::class,
            RemovePermissionFromRoleRequestHandler::class,
            AddLabelToRoleRequestHandler::class,
            RemoveLabelFromRoleRequestHandler::class,
            AddRoleToUserRequestHandler::class,
            RemoveRoleFromUserRequestHandler::class,
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            GetRoleRequestHandler::class,
            fn () => new GetRoleRequestHandler($container->get('role_read_repository'))
        );

        $container->addShared(
            GetUsersWithRoleRequestHandler::class,
            fn () => new GetUsersWithRoleRequestHandler($container->get('role_users_read_repository'))
        );

        $container->addShared(
            GetRolesFromUserRequestHandler::class,
            fn () => new GetRolesFromUserRequestHandler($container->get('user_roles_repository'))
        );

        $container->addShared(
            GetRolesFromCurrentUserRequestHandler::class,
            fn () => new GetRolesFromCurrentUserRequestHandler(
                $container->get('user_roles_repository'),
                $container->get(CurrentUser::class)->getId(),
            ),
        );

        $container->addShared(
            GetPermissionsRequestHandler::class,
            fn () => new GetPermissionsRequestHandler()
        );

        $container->addShared(
            GetUserPermissionsRequestHandler::class,
            fn () => new GetUserPermissionsRequestHandler(
                $container->get(UserPermissionsServiceProvider::USER_PERMISSIONS_READ_REPOSITORY),
                $container->get(CurrentUser::class)->getId(),
                $container->get(CurrentUser::class)->isGodUser(),
            )
        );

        $container->addShared(
            GetRoleLabelsRequestHandler::class,
            fn () => new GetRoleLabelsRequestHandler($container->get('role_labels_read_repository'))
        );

        $container->addShared(
            RolesSearchRequestHandler::class,
            fn () =>  new RolesSearchRequestHandler($container->get('role_search_v3_repository'))
        );

        $container->addShared(
            CreateRoleRequestHandler::class,
            fn () => new CreateRoleRequestHandler(
                $container->get('event_command_bus'),
                new Version4Generator(),
            )
        );

        $container->addShared(
            UpdateRoleRequestHandler::class,
            fn () => new UpdateRoleRequestHandler($container->get('event_command_bus'))
        );

        $container->addShared(
            DeleteRoleRequestHandler::class,
            fn () => new DeleteRoleRequestHandler($container->get('event_command_bus'))
        );

        $container->addShared(
            AddConstraintRequestHandler::class,
            fn () => new AddConstraintRequestHandler($container->get('event_command_bus'))
        );

        $container->addShared(
            UpdateConstraintRequestHandler::class,
            fn () => new UpdateConstraintRequestHandler($container->get('event_command_bus'))
        );

        $container->addShared(
            DeleteConstraintRequestHandler::class,
            fn () => new DeleteConstraintRequestHandler($container->get('event_command_bus'))
        );

        $container->addShared(
            AddPermissionToRoleRequestHandler::class,
            fn () => new AddPermissionToRoleRequestHandler($container->get('event_command_bus'))
        );

        $container->addShared(
            RemovePermissionFromRoleRequestHandler::class,
            fn () => new RemovePermissionFromRoleRequestHandler($container->get('event_command_bus'))
        );

        $container->addShared(
            AddLabelToRoleRequestHandler::class,
            fn () => new AddLabelToRoleRequestHandler(
                $container->get('event_command_bus'),
                $container->get(LabelServiceProvider::JSON_READ_REPOSITORY),
            )
        );

        $container->addShared(
            RemoveLabelFromRoleRequestHandler::class,
            fn () => new RemoveLabelFromRoleRequestHandler(
                $container->get('event_command_bus'),
                $container->get(LabelServiceProvider::JSON_READ_REPOSITORY),
            )
        );

        $container->addShared(
            AddRoleToUserRequestHandler::class,
            fn () => new AddRoleToUserRequestHandler($container->get('event_command_bus'))
        );

        $container->addShared(
            RemoveRoleFromUserRequestHandler::class,
            fn () => new RemoveRoleFromUserRequestHandler($container->get('event_command_bus'))
        );
    }
}

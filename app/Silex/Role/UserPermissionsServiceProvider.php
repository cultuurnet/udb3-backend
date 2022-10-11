<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Role;

use CultuurNet\UDB3\Container\AbstractServiceProvider;
use CultuurNet\UDB3\Role\ReadModel\Permissions\Doctrine\UserPermissionsReadRepository;
use CultuurNet\UDB3\Role\ReadModel\Permissions\Doctrine\UserPermissionsWriteRepository;
use CultuurNet\UDB3\Role\ReadModel\Permissions\UserPermissionsProjector;

final class UserPermissionsServiceProvider extends AbstractServiceProvider
{
    public const USER_ROLES_TABLE = 'user_roles';
    public const ROLE_PERMISSIONS_TABLE = 'role_permissions';

    public const USER_PERMISSIONS_READ_REPOSITORY = 'user_permissions_read_repository';
    public const USER_PERMISSIONS_WRITE_REPOSITORY = 'user_permissions_write_repository';

    public const USER_PERMISSIONS_PROJECTOR = 'roles.user_permissions_projector';

    protected function getProvidedServiceNames(): array
    {
        return [
            self::USER_PERMISSIONS_READ_REPOSITORY,
            self::USER_PERMISSIONS_WRITE_REPOSITORY,
            self::USER_PERMISSIONS_PROJECTOR,
        ];
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->addShared(
            self::USER_PERMISSIONS_READ_REPOSITORY,
            fn () => new UserPermissionsReadRepository(
                $container->get('dbal_connection'),
                self::USER_ROLES_TABLE,
                self::ROLE_PERMISSIONS_TABLE,
            )
        );

        $container->addShared(
            self::USER_PERMISSIONS_WRITE_REPOSITORY,
            fn () => new UserPermissionsWriteRepository(
                $container->get('dbal_connection'),
                self::USER_ROLES_TABLE,
                self::ROLE_PERMISSIONS_TABLE,
            )
        );

        $container->addShared(
            self::USER_PERMISSIONS_PROJECTOR,
            fn () => new UserPermissionsProjector($container->get(self::USER_PERMISSIONS_WRITE_REPOSITORY))
        );
    }
}

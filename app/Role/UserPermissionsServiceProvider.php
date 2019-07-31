<?php

namespace CultuurNet\UDB3\Silex\Role;

use CultuurNet\UDB3\Role\ReadModel\Permissions\Doctrine\UserPermissionsReadRepository;
use CultuurNet\UDB3\Role\ReadModel\Permissions\Doctrine\UserPermissionsWriteRepository;
use CultuurNet\UDB3\Role\ReadModel\Permissions\UserPermissionsProjector;
use Silex\Application;
use Silex\ServiceProviderInterface;
use ValueObjects\StringLiteral\StringLiteral;

class UserPermissionsServiceProvider implements ServiceProviderInterface
{
    const USER_ROLES_TABLE = 'user_roles';
    const ROLE_PERMISSIONS_TABLE = 'role_permissions';

    const USER_PERMISSIONS_READ_REPOSITORY = 'user_permissions_read_repository';
    const USER_PERMISSIONS_WRITE_REPOSITORY = 'user_permissions_write_repository';

    const USER_PERMISSIONS_PROJECTOR = 'roles.user_permissions_projector';
    /**
     * Registers services on the given app.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     */
    public function register(Application $app)
    {
        $app[self::USER_PERMISSIONS_READ_REPOSITORY] = $app->share(
            function ($app) {
                return new UserPermissionsReadRepository(
                    $app['dbal_connection'],
                    new StringLiteral(self::USER_ROLES_TABLE),
                    new StringLiteral(self::ROLE_PERMISSIONS_TABLE)
                );
            }
        );

        $app[self::USER_PERMISSIONS_WRITE_REPOSITORY] = $app->share(
            function ($app) {
                return new UserPermissionsWriteRepository(
                    $app['dbal_connection'],
                    new StringLiteral(self::USER_ROLES_TABLE),
                    new StringLiteral(self::ROLE_PERMISSIONS_TABLE)
                );
            }
        );

        $app[self::USER_PERMISSIONS_PROJECTOR] = $app->share(
            function ($app) {
                return new UserPermissionsProjector($app[self::USER_PERMISSIONS_WRITE_REPOSITORY]);
            }
        );
    }

    /**
     * Bootstraps the application.
     *
     * This method is called after all services are registered
     * and should be used for "dynamic" configuration (whenever
     * a service must be requested).
     */
    public function boot(Application $app)
    {
    }
}

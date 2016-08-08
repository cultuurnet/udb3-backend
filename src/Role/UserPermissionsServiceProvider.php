<?php

namespace CultuurNet\UDB3\Silex\Role;

use CultuurNet\UDB3\Role\ReadModel\Permissions\Doctrine\SchemaConfigurator;
use CultuurNet\UDB3\Role\ReadModel\Permissions\Doctrine\UserPermissionsReadRepository;
use CultuurNet\UDB3\Role\ReadModel\Permissions\Doctrine\UserPermissionsWriteRepository;
use CultuurNet\UDB3\Role\ReadModel\Permissions\UserPermissionsProjector;
use CultuurNet\UDB3\Silex\DatabaseSchemaInstaller;
use Silex\Application;
use Silex\ServiceProviderInterface;
use ValueObjects\String\String as StringLiteral;

class UserPermissionsServiceProvider implements ServiceProviderInterface
{
    const USER_ROLES_TABLE = 'user_roles';
    const ROLE_PERMISSIONS_TABLE = 'role_permissions';

    const USER_PERMISSIONS_SCHEMA = 'roles.user_permissions_schema';

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
        $this->setUpSchemas($app);

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
     * @param Application $app
     */
    private function setUpSchemas(Application $app)
    {
        $app[self::USER_PERMISSIONS_SCHEMA] = $app->share(
            function (Application $app) {
                return new SchemaConfigurator(
                    new StringLiteral(self::USER_ROLES_TABLE),
                    new StringLiteral(self::ROLE_PERMISSIONS_TABLE)
                );
            }
        );

        $app['database.installer'] = $app->extend(
            'database.installer',
            function (DatabaseSchemaInstaller $installer, Application $app) {
                $installer->addSchemaConfigurator(
                    $app[UserPermissionsServiceProvider::USER_PERMISSIONS_SCHEMA]
                );
                return $installer;
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

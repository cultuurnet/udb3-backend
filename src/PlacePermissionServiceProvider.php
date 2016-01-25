<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Silex;

use CultuurNet\UDB3\Place\ReadModel\Permission\Doctrine\SchemaConfigurator;
use CultuurNet\UDB3\UiTID\CdbXmlCreatedByToUserIdResolver;
use Silex\Application;
use Silex\ServiceProviderInterface;
use ValueObjects\String\String as StringLiteral;

class PlacePermissionServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritdoc
     */
    public function register(Application $app)
    {
        $app['place_permission.table_name'] = new StringLiteral('place_permission_readmodel');

        $app['place_permission.repository'] = $app->share(
            function (Application $app) {
                return new \CultuurNet\UDB3\Place\ReadModel\Permission\Doctrine\DBALRepository(
                    $app['place_permission.table_name'],
                    $app['dbal_connection']
                );
            }
        );

        $app['place_permission.projector'] = $app->share(
            function (Application $app) {
                $projector = new \CultuurNet\UDB3\Place\ReadModel\Permission\Projector(
                    $app['place_permission.repository'],
                    new CdbXmlCreatedByToUserIdResolver($app['uitid_users'])
                );

                return $projector;
            }
        );

        $app['place_permission.schema_configurator'] = $app->share(
            function (Application $app) {
                return new SchemaConfigurator($app['place_permission.table_name']);
            }
        );

        // Add our schema configurator to the database installer.
        $app['database.installer'] = $app->extend(
            'database.installer',
            function (DatabaseSchemaInstaller $installer, Application $app) {
                $installer->addSchemaConfigurator(
                    $app['place_permission.schema_configurator']
                );

                return $installer;
            }
        );

        $app['place.security'] = $app->share(
            function (Application $app) {
                $security = new \CultuurNet\UDB3\Place\Security(
                    $app['security.token_storage'],
                    $app['place_permission.repository']
                );

                return $security;
            }
        );
    }

    /**
     * @inheritdoc
     */
    public function boot(Application $app)
    {

    }
}

<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Silex;

use CultuurNet\UDB3\Offer\ReadModel\Permission\Doctrine\SchemaConfigurator;
use CultuurNet\UDB3\UiTID\CdbXmlCreatedByToUserIdResolver;
use Silex\Application;
use Silex\ServiceProviderInterface;
use ValueObjects\String\String as StringLiteral;

class EventPermissionServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritdoc
     */
    public function register(Application $app)
    {
        $app['event_permission.table_name'] = new StringLiteral('event_permission_readmodel');
        $app['event_permission.id_field'] = new StringLiteral('event_id');

        $app['event_permission.repository'] = $app->share(
            function (Application $app) {
                return new \CultuurNet\UDB3\Offer\ReadModel\Permission\Doctrine\DBALRepository(
                    $app['event_permission.table_name'],
                    $app['dbal_connection'],
                    $app['event_permission.id_field']
                );
            }
        );

        $app['event_permission.projector'] = $app->share(
            function (Application $app) {
                $projector = new \CultuurNet\UDB3\Event\ReadModel\Permission\Projector(
                    $app['event_permission.repository'],
                    new CdbXmlCreatedByToUserIdResolver($app['uitid_users'])
                );

                return $projector;
            }
        );

        $app['event_permission.schema_configurator'] = $app->share(
            function (Application $app) {
                return new SchemaConfigurator(
                    $app['event_permission.table_name'],
                    $app['event_permission.id_field']
                );
            }
        );

        // Add our schema configurator to the database installer.
        $app['database.installer'] = $app->extend(
            'database.installer',
            function (DatabaseSchemaInstaller $installer, Application $app) {
                $installer->addSchemaConfigurator(
                    $app['event_permission.schema_configurator']
                );

                return $installer;
            }
        );

        $app['event.security'] = $app->share(
            function (Application $app) {
                $security = new \CultuurNet\UDB3\Offer\Security(
                    $app['security.token_storage'],
                    $app['event_permission.repository']
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

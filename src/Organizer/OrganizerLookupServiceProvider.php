<?php

namespace CultuurNet\UDB3\Silex\Organizer;

use CultuurNet\UDB3\Organizer\ReadModel\Search\Doctrine\SchemaConfigurator;
use CultuurNet\UDB3\Silex\DatabaseSchemaInstaller;
use Silex\Application;
use Silex\ServiceProviderInterface;
use ValueObjects\String\String as StringLiteral;

class OrganizerLookupServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['organizer_lookup'] = $app->share(
            function (Application $app) {
                // At the moment, the index.repository service maintains
                // an index of data for various purposes.
                return $app['index.repository'];
            }
        );

        $app['organizer_website.table_name'] = new StringLiteral('organizer_website');

        $app['organizer_website.repository'] = $app->share(
            function (Application $app) {
                return new \CultuurNet\UDB3\ORganizer\ReadModel\Search\Doctrine\DBALRepository(
                    $app['dbal_connection'],
                    $app['organizer_website.table_name']
                );
            }
        );

        $app['organizer_website.projector'] = $app->share(
            function (Application $app) {
                $projector = new \CultuurNet\UDB3\Organizer\ReadModel\Search\Projector(
                    $app['organizer_website.repository']
                );

                return $projector;
            }
        );

        $app['organizer_website.schema_configurator'] = $app->share(
            function (Application $app) {
                return new SchemaConfigurator($app['organizer_website.table_name']);
            }
        );

        // Add our schema configurator to the database installer.
        $app['database.installer'] = $app->extend(
            'database.installer',
            function (DatabaseSchemaInstaller $installer, Application $app) {
                $installer->addSchemaConfigurator(
                    $app['organizer_website.schema_configurator']
                );

                return $installer;
            }
        );
    }

    public function boot(Application $app)
    {
    }
}

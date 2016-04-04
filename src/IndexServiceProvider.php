<?php

namespace CultuurNet\UDB3\Silex;

use CultuurNet\UDB3\ReadModel\Index\Doctrine\SchemaConfigurator;
use CultuurNet\UDB3\UiTID\CdbXmlCreatedByToUserIdResolver;
use Silex\Application;
use Silex\ServiceProviderInterface;
use ValueObjects\String\String as StringLiteral;

class IndexServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritdoc
     */
    public function register(Application $app)
    {
        $app['index.table_name'] = new StringLiteral('index_readmodel');

        $app['index.repository'] = $app->share(
            function (Application $app) {
                return new \CultuurNet\UDB3\ReadModel\Index\Doctrine\DBALRepository(
                    $app['dbal_connection'],
                    $app['index.table_name'],
                    $app['entity_iri_generator_factory']
                );
            }
        );

        $app['index.projector'] = $app->share(
            function (Application $app) {
                $projector = new \CultuurNet\UDB3\ReadModel\Index\Projector(
                    $app['index.repository'],
                    new CdbXmlCreatedByToUserIdResolver($app['uitid_users']),
                    $app['local_domain'],
                    $app['udb2_domain'],
                    $app['iri_offer_identifier_factory']
                );

                return $projector;
            }
        );

        $app['index.schema_configurator'] = $app->share(
            function (Application $app) {
                return new SchemaConfigurator($app['index.table_name']);
            }
        );

        // Add our schema configurator to the database installer.
        $app['database.installer'] = $app->extend(
            'database.installer',
            function (DatabaseSchemaInstaller $installer, Application $app) {
                $installer->addSchemaConfigurator(
                    $app['index.schema_configurator']
                );

                return $installer;
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

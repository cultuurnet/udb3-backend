<?php

namespace CultuurNet\UDB3\Silex\MyOrganizers;

use CultuurNet\UDB3\MyOrganizers\ReadModel\Doctrine\DBALLookupService;
use CultuurNet\UDB3\MyOrganizers\ReadModel\Doctrine\DBALRepository;
use CultuurNet\UDB3\MyOrganizers\ReadModel\Doctrine\SchemaConfigurator;
use CultuurNet\UDB3\MyOrganizers\ReadModel\Projector;
use CultuurNet\UDB3\MyOrganizers\ReadModel\UDB2Projector;
use CultuurNet\UDB3\Silex\DatabaseSchemaInstaller;
use CultuurNet\UDB3\Silex\User\UserServiceProvider;
use Silex\Application;
use Silex\ServiceProviderInterface;
use ValueObjects\StringLiteral\StringLiteral;

class MyOrganizersServiceProvider implements ServiceProviderInterface
{
    public const PROJECTOR = 'my_organizers.projector';
    public const UDB2_PROJECTOR = 'my_organizers.udb2.projector';

    public const LOOKUP = 'my_organizers.lookup';

    private const REPOSITORY = 'my_organizers.repository';

    private const SCHEMA_CONFIGURATOR = 'my_organizers.schema_configurator';
    private const TABLE_NAME = 'my_organizers.table_name';
    private const DATABASE_INSTALLER = 'database.installer';

    public function register(Application $app)
    {
        $app[self::TABLE_NAME] = new StringLiteral('my_organizers');

        $app[self::SCHEMA_CONFIGURATOR] = $app->share(
            function (Application $app) {
                return new SchemaConfigurator($app[self::TABLE_NAME]);
            }
        );

        // Add our schema configurator to the database installer.
        $app[self::DATABASE_INSTALLER] = $app->extend(
            self::DATABASE_INSTALLER,
            function (DatabaseSchemaInstaller $installer, Application $app) {
                $installer->addSchemaConfigurator(
                    $app[self::SCHEMA_CONFIGURATOR]
                );

                return $installer;
            }
        );

        $app[self::REPOSITORY] = $app->share(
            function (Application $app) {
                return new DBALRepository(
                    $app['dbal_connection'],
                    $app[self::TABLE_NAME]
                );
            }
        );

        $app[self::PROJECTOR] = $app->share(
            function (Application $app) {
                return new Projector(
                    $app[self::REPOSITORY]
                );
            }
        );

        $app[self::UDB2_PROJECTOR] = $app->share(
            function (Application $app) {
                return new UDB2Projector(
                    $app[self::REPOSITORY],
                    $app[UserServiceProvider::ITEM_BASE_ADAPTER_FACTORY]
                );
            }
        );

        $app[self::LOOKUP] = $app->share(
            function (Application $app) {
                return new DBALLookupService(
                    $app['dbal_connection'],
                    $app[self::TABLE_NAME],
                    $app['organizer_iri_generator']
                );
            }
        );
    }

    public function boot(Application $app)
    {
    }
}

<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Silex;

use CultuurNet\UDB3\Doctrine\DBAL\SchemaConfiguratorInterface;
use CultuurNet\UDB3\Silex\Labels\LabelServiceProvider;
use Silex\Application;

class DatabaseSchemaInstaller implements DatabaseSchemaInstallerInterface
{

    protected $app;

    /**
     * @var SchemaConfiguratorInterface[]
     */
    protected $schemaConfigurators;

    public function __construct(Application $app)
    {
        $this->app = $app;

        $this->schemaConfigurators = [];
    }

    public function addSchemaConfigurator(
        SchemaConfiguratorInterface $schemaConfigurator
    ) {
        $this->schemaConfigurators[] = $schemaConfigurator;
    }

    public function installSchema()
    {
        // Combination of:
        // - one real merged event store.
        // - two helpers for unique event store for organizers and labels.
        // - and some MySQL read models.
        $stores = array(
            $this->app['dbal_event_store'],
            $this->app[LabelServiceProvider::UNIQUE_EVENT_STORE],
            $this->app['organizer_store'],
            $this->app['event_relations_repository'],
            $this->app['place_relations_repository'],
            $this->app['variations.search'],
        );

        /** @var \Doctrine\DBAL\Connection $connection */
        $connection = $this->app['dbal_connection'];

        $schemaManager = $connection->getSchemaManager();
        $schema = $schemaManager->createSchema();

        foreach ($stores as $store) {
            $table = $store->configureSchema($schema);
            if ($table) {
                $schemaManager->createTable($table);
            }
        }

        foreach ($this->schemaConfigurators as $configurator) {
            $configurator->configure($schemaManager);
        }
    }
}

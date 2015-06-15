<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Silex;

use Silex\Application;

class DatabaseSchemaInstaller
{

    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function installSchema()
    {
        /** @var \Broadway\EventStore\DBALEventStore[] $stores */
        $stores = array(
            $this->app['event_store'],
            $this->app['place_store'],
            $this->app['organizer_store'],
            $this->app['event_relations_repository'],
            $this->app['variations.event_store'],
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
    }
}

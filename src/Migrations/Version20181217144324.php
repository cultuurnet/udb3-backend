<?php

namespace CultuurNet\UDB3\Silex\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20181217144324 extends AbstractMigration
{
    const EVENT_STORE_TABLES = [
        'events',
        'places',
        'organizers',
        'labels',
        'roles',
        'media_objects',
        'variations'
    ];

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        foreach (self::EVENT_STORE_TABLES as $eventStoreTable) {
            $schema->dropTable($eventStoreTable);
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        foreach (self::EVENT_STORE_TABLES as $eventStoreTable) {
            $this->createEventStoreTable($schema, $eventStoreTable);
        }
    }

    private function createEventStoreTable(Schema $schema, StringLiteral $name)
    {
        // @see \Broadway\EventStore\DBALEventStore
        $table = $schema->createTable($name);

        $table->addColumn('id', 'integer', array('autoincrement' => true));
        $table->addColumn('uuid', 'guid', array('length' => 36));
        $table->addColumn('playhead', 'integer', array('unsigned' => true));
        $table->addColumn('payload', 'text');
        $table->addColumn('metadata', 'text');
        $table->addColumn('recorded_on', 'string', array('length' => 32));
        $table->addColumn('type', 'text');

        $table->setPrimaryKey(array('id'));
        $table->addUniqueIndex(array('uuid', 'playhead'));
    }
}

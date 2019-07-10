<?php

namespace CultuurNet\UDB3\Silex\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Creates the organizer store.
 */
class Version19700101000001 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // @see \Broadway\EventStore\DBALEventStore
        $table = $schema->createTable('organizers');

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

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->dropTable('organizers');
    }
}

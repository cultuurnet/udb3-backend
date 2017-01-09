<?php

namespace CultuurNet\UDB3\Silex\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Creates a new event store table for all aggregates.
 */
class Version20170109103905 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $table = $schema->createTable('event_store');

        $table->addColumn('id', 'integer', array('autoincrement' => true));
        $table->addColumn('uuid', 'guid', array('length' => 36,));
        $table->addColumn('playhead', 'integer', array('unsigned' => true));
        $table->addColumn('payload', 'text');
        $table->addColumn('metadata', 'text');
        $table->addColumn('recorded_on', 'string', array('length' => 32));
        $table->addColumn('type', 'string', array('length' => 128));
        $table->addColumn('aggregate_type', 'string', array('length' => 128));

        $table->setPrimaryKey(array('id'));

        $table->addUniqueIndex(array('uuid', 'playhead'));

        $table->addIndex(['type']);
        $table->addIndex(['aggregate_type']);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->dropTable('event_store');
    }
}

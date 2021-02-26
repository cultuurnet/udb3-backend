<?php

namespace CultuurNet\UDB3\Silex\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Creates the places store.
 */
class Version19700101000002 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // @see \Broadway\EventStore\DBALEventStore
        $table = $schema->createTable('places');

        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('uuid', 'guid', ['length' => 36]);
        $table->addColumn('playhead', 'integer', ['unsigned' => true]);
        $table->addColumn('payload', 'text');
        $table->addColumn('metadata', 'text');
        $table->addColumn('recorded_on', 'string', ['length' => 32]);
        $table->addColumn('type', 'text');

        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['uuid', 'playhead']);
    }


    public function down(Schema $schema)
    {
        $schema->dropTable('places');
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Creates a new event store table for all aggregates.
 */
class Version20170109103905 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $table = $schema->createTable('event_store');

        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('uuid', 'guid', ['length' => 36]);
        $table->addColumn('playhead', 'integer', ['unsigned' => true]);
        $table->addColumn('payload', 'text');
        $table->addColumn('metadata', 'text');
        $table->addColumn('recorded_on', 'string', ['length' => 32]);
        $table->addColumn('type', 'string', ['length' => 128]);
        $table->addColumn('aggregate_type', 'string', ['length' => 128]);

        $table->setPrimaryKey(['id']);

        $table->addUniqueIndex(['uuid', 'playhead']);

        $table->addIndex(['type']);
        $table->addIndex(['aggregate_type']);
    }


    public function down(Schema $schema)
    {
        $schema->dropTable('event_store');
    }
}

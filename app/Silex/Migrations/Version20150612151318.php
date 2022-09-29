<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Creates the event store for variations.
 */
class Version20150612151318 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // @see \Broadway\EventStore\DBALEventStore
        $table = $schema->createTable('variations');

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
        $schema->dropTable('variations');
    }
}

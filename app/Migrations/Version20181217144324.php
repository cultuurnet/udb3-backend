<?php

namespace CultuurNet\UDB3\Silex\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use ValueObjects\StringLiteral\StringLiteral;

class Version20181217144324 extends AbstractMigration
{
    public const EVENT_STORE_TABLES = [
        'events',
        'places',
        'organizers',
        'labels',
        'roles',
        'media_objects',
        'variations',
    ];


    public function up(Schema $schema)
    {
        foreach (self::EVENT_STORE_TABLES as $eventStoreTable) {
            $schema->dropTable($eventStoreTable);
        }
    }


    public function down(Schema $schema)
    {
        foreach (self::EVENT_STORE_TABLES as $eventStoreTable) {
            $this->createEventStoreTable($schema, new StringLiteral($eventStoreTable));
        }
    }

    private function createEventStoreTable(Schema $schema, StringLiteral $name)
    {
        // @see \Broadway\EventStore\DBALEventStore
        $table = $schema->createTable($name->toNative());

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
}

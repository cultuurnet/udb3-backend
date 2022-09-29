<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Creates the event_relations table.
 */
class Version19700101000003 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // @see \CultuurNet\UDB3\Event\ReadModel\Relations\Doctrine\DBALRepository
        $table = $schema->createTable('event_relations');

        $table->addColumn(
            'event',
            'string',
            ['length' => 32, 'notnull' => false]
        );
        $table->addColumn(
            'organizer',
            'string',
            ['length' => 32, 'notnull' => false]
        );
        $table->addColumn(
            'place',
            'string',
            ['length' => 32, 'notnull' => false]
        );

        $table->setPrimaryKey(['event']);
    }


    public function down(Schema $schema)
    {
        $schema->dropTable('event_relations');
    }
}

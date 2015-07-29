<?php

namespace CultuurNet\UDB3\Silex\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Creates the event_relations table.
 */
class Version19700101000003 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // @see \CultuurNet\UDB3\Event\ReadModel\Relations\Doctrine\DBALRepository
        $table = $schema->createTable('event_relations');

        $table->addColumn(
            'event',
            'string',
            array('length' => 32, 'notnull' => false)
        );
        $table->addColumn(
            'organizer',
            'string',
            array('length' => 32, 'notnull' => false)
        );
        $table->addColumn(
            'place',
            'string',
            array('length' => 32, 'notnull' => false)
        );

        $table->setPrimaryKey(array('event'));
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->dropTable('event_relations');
    }
}

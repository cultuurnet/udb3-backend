<?php

namespace CultuurNet\UDB3\Silex\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Creates the search index table for variations.
 */
class Version20150615114627 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // @see \CultuurNet\UDB3\Variations\ReadModel\Search\Doctrine\DBALRepository
        $table = $schema->createTable('event_variation_search_index');

        $table->addColumn(
            'id',
            'string',
            ['length' => 36, 'notnull' => true]
        );
        $table->addColumn(
            'event',
            'text',
            ['notnull' => true]
        );
        $table->addColumn(
            'owner',
            'string',
            ['length' => 36, 'notnull' => true]
        );
        $table->addColumn(
            'purpose',
            'text',
            ['notnull' => true]
        );

        $table->addColumn(
            'inserted',
            'integer',
            ['notnull' => true, 'unsigned' => true]
        );

        $table->setPrimaryKey(['id']);

        $table->addIndex(['inserted']);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->dropTable('event_variation_search_index');
    }
}

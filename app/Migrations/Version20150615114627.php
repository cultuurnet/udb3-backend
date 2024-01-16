<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20150615114627 extends AbstractMigration
{
    public function up(Schema $schema): void
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


    public function down(Schema $schema): void
    {
        $schema->dropTable('event_variation_search_index');
    }
}

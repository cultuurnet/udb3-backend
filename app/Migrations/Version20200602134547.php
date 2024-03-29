<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20200602134547 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->createTable('productions');
        $table->addColumn(
            'event_id',
            'guid',
            [
                'length' => 36,
                'notnull' => true,
            ]
        );
        $table->addColumn(
            'production_id',
            'guid',
            [
                'length' => 36,
                'notnull' => true,
            ]
        );
        $table->addColumn(
            'name',
            'string',
            [
                'length' => 32,
                'notnull' => true,
            ]
        );
        $table->addColumn(
            'added_at',
            'date_immutable',
            [
                'notnull' => true,
            ]
        );

        $table->setPrimaryKey(['event_id']);
        $table->addIndex(['production_id']);
        $table->addIndex(['name'], 'idx_search_name', ['fulltext']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('productions');
    }
}

<?php

namespace CultuurNet\UDB3\Silex\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20200602134547 extends AbstractMigration
{
    public function up(Schema $schema)
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
        $table->addIndex(['name']);
    }

    public function down(Schema $schema)
    {
        $schema->dropTable('productions');
    }
}

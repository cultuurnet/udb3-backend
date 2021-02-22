<?php

namespace CultuurNet\UDB3\Silex\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20210222150300 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->createTable('labels_import');

        $table->addColumn(
            'offer_id',
            'guid',
            [
                'length' => 36,
                'notnull' => true,
            ]
        );
        $table->addColumn(
            'offer_type',
            'string',
            [
                'length' => 32,
                'notnull' => true,
            ]
        );
        $table->addColumn(
            'label',
            'string',
            [
                'length' => 255,
                'notnull' => true,
            ]
        );

        $table->setPrimaryKey(
            [
                'offer_id',
            ],
            'offer_id_index'
        );
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('labels_import');
    }
}

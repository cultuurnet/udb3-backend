<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

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

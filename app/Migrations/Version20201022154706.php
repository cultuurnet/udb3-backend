<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20201022154706 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->createTable('offer_popularity');

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
            'popularity',
            'bigint',
            [
                'notnull' => true,
            ]
        );
        $table->addColumn(
            'creation_date',
            'datetime',
            [
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
        $schema->dropTable('offer_popularity');
    }
}

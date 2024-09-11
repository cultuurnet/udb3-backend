<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20150817113039 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->createTable('index_readmodel');

        $table->addColumn(
            'entity_id',
            'guid',
            ['length' => 36, 'notnull' => true]
        );
        $table->addColumn(
            'entity_type',
            'string',
            ['length' => 36, 'notnull' => true]
        );
        $table->addColumn(
            'title',
            'text'
        );
        $table->addColumn(
            'uid',
            'guid',
            ['length' => 36, 'notnull' => true]
        );
        $table->addColumn(
            'zip',
            'text'
        );
        $table->addColumn(
            'created',
            'text',
            ['length' => 36, 'notnull' => true]
        );

        $table->setPrimaryKey(['entity_id', 'entity_type']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('index_readmodel');
    }
}

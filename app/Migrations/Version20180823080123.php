<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20180823080123 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->createTable('my_organizers');

        $table->addColumn(
            'id',
            'guid',
            ['length' => 36, 'notnull' => true]
        );
        $table->addColumn(
            'uid',
            'guid',
            ['length' => 36, 'notnull' => true]
        );
        $table->addColumn(
            'created',
            'string',
            ['length' => 32, 'notnull' => true]
        );
        $table->addColumn(
            'updated',
            'string',
            ['length' => 32, 'notnull' => true]
        );

        $table->setPrimaryKey(['id']);

        $table->addIndex(['uid']);
    }


    public function down(Schema $schema): void
    {
        $schema->dropTable('my_organizers');
    }
}

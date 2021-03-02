<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180823080123 extends AbstractMigration
{
    public function up(Schema $schema)
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


    public function down(Schema $schema)
    {
        $schema->dropTable('my_organizers');
    }
}

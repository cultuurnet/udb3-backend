<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20160125121509 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->createTable('place_permission_readmodel');

        $table->addColumn(
            'place_id',
            'guid',
            ['length' => 36, 'notnull' => true]
        );
        $table->addColumn(
            'user_id',
            'guid',
            ['length' => 36, 'notnull' => true]
        );

        $table->setPrimaryKey(['place_id', 'user_id']);
    }


    public function down(Schema $schema): void
    {
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160125121509 extends AbstractMigration
{
    public function up(Schema $schema)
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


    public function down(Schema $schema)
    {
    }
}

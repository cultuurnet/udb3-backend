<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20180711095920 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->createTable('organizer_permission_readmodel');

        $table->addColumn(
            'organizer_id',
            'guid',
            ['length' => 36, 'notnull' => true]
        );
        $table->addColumn(
            'user_id',
            'guid',
            ['length' => 36, 'notnull' => true]
        );

        $table->setPrimaryKey(['organizer_id', 'user_id']);
    }


    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}

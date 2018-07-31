<?php

namespace CultuurNet\UDB3\Silex\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180711095920 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $table = $schema->createTable('organizer_permission_readmodel');

        $table->addColumn(
            'organizer_id',
            'guid',
            array('length' => 36, 'notnull' => true)
        );
        $table->addColumn(
            'user_id',
            'guid',
            array('length' => 36, 'notnull' => true)
        );

        $table->setPrimaryKey(['organizer_id', 'user_id']);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}

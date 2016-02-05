<?php

namespace CultuurNet\UDB3\Silex\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160125121509 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $table = $schema->createTable('place_permission_readmodel');

        $table->addColumn(
            'place_id',
            'guid',
            array('length' => 36, 'notnull' => true)
        );
        $table->addColumn(
            'user_id',
            'guid',
            array('length' => 36, 'notnull' => true)
        );

        $table->setPrimaryKey(['place_id', 'user_id']);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}

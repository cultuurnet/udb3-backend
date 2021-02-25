<?php

namespace CultuurNet\UDB3\Silex\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20200526130622 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema)
    {
        $schema->dropTable('my_organizers');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // Copied from Version20180823080123::up()
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
}

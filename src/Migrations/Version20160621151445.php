<?php

namespace CultuurNet\UDB3\Silex\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160621151445 extends AbstractMigration
{
    const ROLES = 'roles';

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $table = $schema->createTable(self::ROLES);

        $table->addColumn('id', 'integer', array('autoincrement' => true));
        $table->addColumn('uuid', 'guid', array('length' => 36));
        $table->addColumn('name', 'string', array('length' => 255));

        $table->setPrimaryKey(array('id'));
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->dropTable(self::ROLES);

    }
}

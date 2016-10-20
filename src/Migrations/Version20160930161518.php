<?php

namespace CultuurNet\UDB3\Silex\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160930161518 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $table = $schema->createTable('organizer_unique_websites');

        $table->addColumn('uuid_col', Type::GUID)
            ->setLength(36)
            ->setNotnull(true);

        $table->addColumn('unique_col', Type::STRING)
            ->setLength(255)
            ->setNotnull(true);

        $table->setPrimaryKey(['uuid_col']);
        $table->addUniqueIndex(['uuid_col']);
        $table->addUniqueIndex(['unique_col']);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->dropTable('organizer_unique_websites');
    }
}

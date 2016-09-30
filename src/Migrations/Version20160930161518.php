<?php

namespace CultuurNet\UDB3\Silex\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

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
        $organizerWebsiteTable = $schema->createTable('organizer_website');

        $organizerWebsiteTable->addColumn('uuid', 'guid')
            ->setLength(36)
            ->setNotnull(true);

        $organizerWebsiteTable->addColumn('title', 'string')
            ->setLength(255)
            ->setNotnull(true);

        $organizerWebsiteTable->addColumn('website', 'string')
            ->setLength(255)
            ->setNotnull(true);

        $organizerWebsiteTable->setPrimaryKey(array('uuid'));
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->dropTable('organizer_website');
    }
}

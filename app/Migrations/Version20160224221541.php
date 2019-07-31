<?php

namespace CultuurNet\UDB3\Silex\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160224221541 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $table = $schema->getTable('event_variation_search_index');

        // Since we copied data in previous migrations, we can alter the "origin_url" column and drop the "event"
        // column.
        $table->changeColumn(
            'origin_url',
            array('notnull' => true)
        );

        $table->dropColumn(
            'event'
        );
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $table = $schema->getTable('event_variation_search_index');

        $table->changeColumn(
            'origin_url',
            array('notnull' => false)
        );

        $table->addColumn(
            'event',
            'text'
        );
    }

    /**
     * @param Schema $schema
     */
    public function postDown(Schema $schema)
    {
        $this->connection->executeQuery("UPDATE event_variation_search_index SET event = origin_url");
    }
}

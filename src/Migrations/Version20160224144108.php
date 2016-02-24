<?php

namespace CultuurNet\UDB3\Silex\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160224144108 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $table = $schema->getTable('event_variation_search_index');

        // The renameColumn function does not work here.
        // It will drop the column and recreate it.
        $table->addColumn(
            'offer',
            'text'
        );

        $table->addColumn(
            'type',
            'string',
            array('length' => 100, 'default' => 'event', 'notnull' => true)
        );
    }

    /**
     * @param Schema $schema
     */
    public function postUp(Schema $schema)
    {
        // copy data from "event" to "offer" column.
        $this->connection->executeQuery("UPDATE event_variation_search_index SET offer = event");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $table = $schema->getTable('event_variation_search_index');

        $table->dropColumn(
            'offer'
        );

        $table->dropColumn(
            'type'
        );
    }
}

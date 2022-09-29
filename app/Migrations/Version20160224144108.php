<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160224144108 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $table = $schema->getTable('event_variation_search_index');

        // The renameColumn function does not work here.
        // It will drop the column and recreate it.
        $table->addColumn(
            'origin_url',
            'text'
        );
    }


    public function postUp(Schema $schema)
    {
        // copy data from "event" to "origin_url" column.
        $this->connection->executeQuery('UPDATE event_variation_search_index SET origin_url = event');
    }


    public function down(Schema $schema)
    {
        $table = $schema->getTable('event_variation_search_index');

        $table->dropColumn(
            'origin_url'
        );
    }
}

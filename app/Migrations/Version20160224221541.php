<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20160224221541 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->getTable('event_variation_search_index');

        // Since we copied data in previous migrations, we can alter the "origin_url" column and drop the "event"
        // column.
        $table->changeColumn(
            'origin_url',
            ['notnull' => true]
        );

        $table->dropColumn(
            'event'
        );
    }


    public function down(Schema $schema): void
    {
        $table = $schema->getTable('event_variation_search_index');

        $table->changeColumn(
            'origin_url',
            ['notnull' => false]
        );

        $table->addColumn(
            'event',
            'text'
        );
    }


    public function postDown(Schema $schema): void
    {
        $this->connection->executeQuery('UPDATE event_variation_search_index SET event = origin_url');
    }
}

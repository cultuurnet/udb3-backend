<?php

namespace CultuurNet\UDB3\Silex\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Increases the length of columns in the event_relations table.
 */
class Version20150403111222 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        foreach (['event', 'place', 'organizer'] as $column) {
            $schema->getTable('event_relations')->getColumn(
                $column
            )->setLength(36);
        }
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        foreach (['event', 'place', 'organizer'] as $column) {
            $schema->getTable('event_relations')->getColumn(
                $column
            )->setLength(32);
        }
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20150403111222 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        foreach (['event', 'place', 'organizer'] as $column) {
            $schema->getTable('event_relations')->getColumn(
                $column
            )->setLength(36);
        }
    }


    public function down(Schema $schema): void
    {
        foreach (['event', 'place', 'organizer'] as $column) {
            $schema->getTable('event_relations')->getColumn(
                $column
            )->setLength(32);
        }
    }
}

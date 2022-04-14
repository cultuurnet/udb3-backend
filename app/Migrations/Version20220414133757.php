<?php declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220414133757 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $schema->getTable('event_relations')
            ->addIndex(['organizer'], 'organizer_index')
            ->addIndex(['place'], 'place_index');

        $schema->getTable('place_relations')
            ->addIndex(['organizer'], 'organizer_index');
    }

    public function down(Schema $schema) : void
    {
        $schema->getTable('event_relations')
            ->dropIndex('organizer_index');

        $schema->getTable('event_relations')
            ->dropIndex('place_index');

        $schema->getTable('place_relations')
            ->dropIndex('organizer_index');
    }
}

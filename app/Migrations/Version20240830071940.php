<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

class Version20240830071940 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // Concept of the table changed, it now contains cluster ids instead of place uuids
        $schema->dropTable('duplicate_places_removed_from_cluster');
        $table = $schema->createTable('duplicate_places_removed_from_cluster');
        $table->addColumn('cluster_id', Types::STRING)->setNotnull(true)->setLength(40);//SHA1 length
        $table->setPrimaryKey(['cluster_id']);

        $table = $schema->getTable('duplicate_places_import');
        $table->setPrimaryKey(['cluster_id', 'place_uuid']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('duplicate_places_removed_from_cluster');
        $table = $schema->createTable('duplicate_places_removed_from_cluster');
        $table->addColumn('place_uuid', Types::GUID)->setLength(36)->setNotnull(true);

        $table = $schema->getTable('duplicate_places_import');
        $table->dropPrimaryKey();
    }
}

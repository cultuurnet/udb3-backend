<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/*
 * Adding primary keys
 * */
class Version20240830071940 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->getTable('duplicate_places_removed_from_cluster');
        $table->setPrimaryKey(['place_uuid']);

        $table = $schema->getTable('duplicate_places_import');
        $table->setPrimaryKey(['cluster_id', 'place_uuid']);
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('duplicate_places_import');
        $table->dropPrimaryKey();

        $table = $schema->getTable('duplicate_places_removed_from_cluster');
        $table->dropPrimaryKey();
    }
}

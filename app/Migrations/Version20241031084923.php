<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20241031084923 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $schema->dropTable('duplicate_places_removed_from_cluster_import');
    }

    public function down(Schema $schema): void
    {
        $table = $schema->createTable('duplicate_places_removed_from_cluster_import');
        $table->addColumn('place_uuid', Types::GUID)->setLength(36)->setNotnull(true);
    }
}

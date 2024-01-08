<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20220609104420 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->createTable('duplicate_places_removed_from_cluster');

        $table->addColumn('place_uuid', Types::GUID)->setLength(36)->setNotnull(true);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('duplicate_places_removed_from_cluster');
    }
}

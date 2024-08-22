<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Types\Type;

class Version20240822071940 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->createTable('duplicate_places_import');
        $table->addColumn('cluster_id', Types::STRING)->setNotnull(true)->setLength(40);//SHA1 length
        $table->addColumn('place_uuid', Types::GUID)->setLength(36)->setNotnull(true);
        $table->addColumn('canonical', Types::GUID)->setLength(36)->setNotnull(false)->setDefault(null);

        $table = $schema->createTable('duplicate_places_removed_from_cluster_import');
        $table->addColumn('place_uuid', Types::GUID)->setLength(36)->setNotnull(true);

        $table = $schema->getTable('duplicate_places');
        $table->changeColumn(
            'cluster_id',
            [
                'type' => Type::getType(Types::STRING),
                'length' => 40, //SHA1 length
            ]
        );
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('duplicate_places_import');
        $schema->dropTable('duplicate_places_removed_from_cluster_import');
        $table = $schema->getTable('duplicate_places');
        $table->changeColumn(
            'cluster_id',
            [
                'type' => Type::getType(Types::BIGINT),
            ]
        );
    }
}

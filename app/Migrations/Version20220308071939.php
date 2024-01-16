<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

class Version20220308071939 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->createTable('duplicate_places');

        $table->addColumn('cluster_id', Types::BIGINT)->setNotnull(true);
        $table->addColumn('place_uuid', Types::GUID)->setLength(36)->setNotnull(true);
        $table->addColumn('is_canonical', Types::BOOLEAN)->setNotnull(true);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('duplicate_places');
    }
}

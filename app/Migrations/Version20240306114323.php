<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20240306114323 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->createTable('ownership_search');

        $table->addColumn('id', Types::GUID)->setLength(36)->setNotnull(true);
        $table->addColumn('item_id', Types::GUID)->setLength(36)->setNotnull(true);
        $table->addColumn('item_type', Types::STRING)->setNotnull(true);
        $table->addColumn('owner_id', Types::STRING)->setNotnull(true);

        $table->setPrimaryKey(['id']);
        $table->addIndex(['item_id']);
        $table->addIndex(['owner_id']);
        $table->addUniqueIndex(['item_id', 'owner_id']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('ownership_search');
    }
}

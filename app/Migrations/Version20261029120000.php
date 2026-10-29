<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20261029120000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->createTable('media_object_relations');

        $table->addColumn('media_object_id', Types::GUID)->setLength(36)->setNotnull(true);
        $table->addColumn('item_type', Types::STRING)->setLength(16)->setNotnull(true);
        $table->addColumn('item_id', Types::GUID)->setLength(36)->setNotnull(true);

        $table->setPrimaryKey(['media_object_id', 'item_id']);
        $table->addIndex(['item_id']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('media_object_relations');
    }
}

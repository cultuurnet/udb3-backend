<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership\Repositories\Search;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;

final class OwnershipSearchSchemaConfigurator
{
    public static function getTableDefinition(Schema $schema): Table
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

        return $table;
    }
}

<?php

namespace CultuurNet\UDB3\Event\Productions\Doctrine;

use CultuurNet\UDB3\Event\Productions\ProductionRepository;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;

class ProductionSchemaConfigurator
{
    /**
     * @return Table
     */
    public static function getTableDefinition(Schema $schema)
    {
        $table = $schema->createTable(ProductionRepository::TABLE_NAME);

        $table->addColumn('event_id', Type::GUID)->setLength(36)->setNotnull(true);
        $table->addColumn('production_id', Type::GUID)->setLength(36)->setNotnull(true);
        $table->addColumn('name', Type::STRING)->setLength(255)->setNotnull(true);
        $table->addColumn('added_at', Type::DATE_IMMUTABLE)->setNotnull(true);

        $table->setPrimaryKey(['event_id']);
        $table->addIndex(['production_id']);
        $table->addIndex(['name']);

        return $table;
    }
}

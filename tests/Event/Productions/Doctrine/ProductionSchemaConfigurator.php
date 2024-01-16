<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Productions\Doctrine;

use CultuurNet\UDB3\Event\Productions\DBALProductionRepository;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;

class ProductionSchemaConfigurator
{
    public static function getTableDefinition(Schema $schema): Table
    {
        $table = $schema->createTable(DBALProductionRepository::TABLE_NAME);

        $table->addColumn('event_id', Types::GUID)->setLength(36)->setNotnull(true);
        $table->addColumn('production_id', Types::GUID)->setLength(36)->setNotnull(true);
        $table->addColumn('name', Types::STRING)->setLength(255)->setNotnull(true);
        $table->addColumn('added_at', Types::DATE_IMMUTABLE)->setNotnull(true);

        $table->setPrimaryKey(['event_id']);
        $table->addIndex(['production_id']);
        $table->addIndex(['name']);

        return $table;
    }
}

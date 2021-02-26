<?php

namespace CultuurNet\UDB3\Event\Productions\Doctrine;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;

class SimilarEventsSchemaConfigurator
{
    private const TABLE = 'similar_events';

    /**
     * @return Table
     */
    public static function getTableDefinition(Schema $schema)
    {
        $table = $schema->createTable(self::TABLE);

        $table->addColumn('similarity', Type::DECIMAL)->setNotnull(true);
        $table->addColumn('event1', Type::GUID)->setLength(36)->setNotnull(true);
        $table->addColumn('event2', Type::GUID)->setLength(36)->setNotnull(true);

        return $table;
    }
}

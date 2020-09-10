<?php

namespace CultuurNet\UDB3\Event\Productions\Doctrine;

use CultuurNet\UDB3\Doctrine\DBAL\SchemaConfiguratorInterface;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;

class SkippedSimilarEventsSchemaConfigurator
{
    private const TABLE = 'skipped_similar_events';

    /**
     * @param Schema $schema
     * @return Table
     */
    public static function getTableDefinition(Schema $schema)
    {
        $table = $schema->createTable(self::TABLE);

        $table->addColumn('event1', Type::GUID)->setLength(36)->setNotnull(true);
        $table->addColumn('event2', Type::GUID)->setLength(36)->setNotnull(true);

        return $table;
    }
}

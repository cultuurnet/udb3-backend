<?php

namespace CultuurNet\UDB3\Event\Productions\Doctrine;

use CultuurNet\UDB3\Event\Productions\SkippedSimilarEventsRepository;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;

class SkippedSimilarEventsSchemaConfigurator
{
    /**
     * @param Schema $schema
     * @return Table
     */
    public static function getTableDefinition(Schema $schema)
    {
        $table = $schema->createTable(SkippedSimilarEventsRepository::TABLE_NAME);

        $table->addColumn('event1', Type::GUID)->setLength(36)->setNotnull(true);
        $table->addColumn('event2', Type::GUID)->setLength(36)->setNotnull(true);

        return $table;
    }
}

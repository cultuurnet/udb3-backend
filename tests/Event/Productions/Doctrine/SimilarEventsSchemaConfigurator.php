<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Productions\Doctrine;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;

class SimilarEventsSchemaConfigurator
{
    private const TABLE = 'similar_events';

    public static function getTableDefinition(Schema $schema): Table
    {
        $table = $schema->createTable(self::TABLE);

        $table->addColumn('similarity', Types::DECIMAL, ['precision' => 10, 'scale' => 2])->setNotnull(true);
        $table->addColumn('event1', Types::GUID)->setLength(36)->setNotnull(true);
        $table->addColumn('event2', Types::GUID)->setLength(36)->setNotnull(true);

        return $table;
    }
}

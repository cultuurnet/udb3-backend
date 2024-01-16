<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Productions\Doctrine;

use CultuurNet\UDB3\Event\Productions\SkippedSimilarEventsRepository;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;

class SkippedSimilarEventsSchemaConfigurator
{
    public static function getTableDefinition(Schema $schema): Table
    {
        $table = $schema->createTable(SkippedSimilarEventsRepository::TABLE_NAME);

        $table->addColumn('event1', Types::GUID)->setLength(36)->setNotnull(true);
        $table->addColumn('event2', Types::GUID)->setLength(36)->setNotnull(true);

        return $table;
    }
}

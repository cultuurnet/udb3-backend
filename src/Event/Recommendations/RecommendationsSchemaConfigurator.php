<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Recommendations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;

final class RecommendationsSchemaConfigurator
{
    public static function getTableDefinition(Schema $schema): Table
    {
        $table = $schema->createTable('event_recommendations');

        $table->addColumn('event_id', Type::GUID)->setLength(36)->setNotnull(true);
        $table->addColumn('recommended_event_id', Type::GUID)->setLength(36)->setNotnull(true);
        $table->addColumn('score', 'decimal', ['notnull' => true, 'scale' => 2]);
        $table->addIndex(['event_id']);
        $table->addIndex(['recommended_event_id']);

        return $table;
    }
}

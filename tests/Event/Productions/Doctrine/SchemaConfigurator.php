<?php

namespace CultuurNet\UDB3\Event\Productions\Doctrine;

use CultuurNet\UDB3\Doctrine\DBAL\SchemaConfiguratorInterface;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;

class SchemaConfigurator implements SchemaConfiguratorInterface
{
    private const TABLE = 'productions';

    public function configure(AbstractSchemaManager $schemaManager)
    {
        $schema = $schemaManager->createSchema();

        if (!$schema->hasTable(self::TABLE)) {
            $table = $this->createTable($schema);
            $schemaManager->createTable($table);
        }
    }

    /**
     * @param Schema $schema
     * @return Table
     */
    private function createTable(Schema $schema)
    {
        $table = $schema->createTable(self::TABLE);

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

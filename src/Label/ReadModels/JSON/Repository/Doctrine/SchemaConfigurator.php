<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Doctrine;

use CultuurNet\UDB3\Doctrine\DBAL\SchemaConfiguratorInterface;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;

class SchemaConfigurator implements SchemaConfiguratorInterface
{
    public const UUID_COLUMN = 'uuid_col';
    public const NAME_COLUMN = 'name';
    public const VISIBLE_COLUMN = 'visible';
    public const PRIVATE_COLUMN = 'private';
    public const EXCLUDED_COLUMN = 'excluded';

    private string $tableName;

    public function __construct(string $tableName)
    {
        $this->tableName = $tableName;
    }

    public function configure(AbstractSchemaManager $schemaManager): void
    {
        $schema = $schemaManager->createSchema();

        if (!$schema->hasTable($this->tableName)) {
            $table = $this->createTable($schema, $this->tableName);

            $schemaManager->createTable($table);
        }
    }

    private function createTable(Schema $schema, string $tableName): Table
    {
        $table = $schema->createTable($tableName);

        $table->addColumn(self::UUID_COLUMN, Types::GUID)
            ->setLength(36)
            ->setNotnull(true);

        $table->addColumn(self::NAME_COLUMN, Types::STRING)
            ->setLength(255)
            ->setNotnull(true);

        $table->addColumn(self::VISIBLE_COLUMN, Types::BOOLEAN)
            ->setNotnull(true)
            ->setDefault(true);

        $table->addColumn(self::PRIVATE_COLUMN, Types::BOOLEAN)
            ->setNotnull(true)
            ->setDefault(false);

        $table->addColumn(self::EXCLUDED_COLUMN, Types::BOOLEAN)
            ->setNotnull(true)
            ->setDefault(false);

        $table->setPrimaryKey([self::UUID_COLUMN]);
        $table->addUniqueIndex([self::UUID_COLUMN]);
        $table->addUniqueIndex([self::NAME_COLUMN]);
        $table->addIndex([self::EXCLUDED_COLUMN]);

        return $table;
    }
}

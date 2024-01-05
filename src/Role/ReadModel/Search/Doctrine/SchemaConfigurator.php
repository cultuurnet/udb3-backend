<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\ReadModel\Search\Doctrine;

use CultuurNet\UDB3\Doctrine\DBAL\SchemaConfiguratorInterface;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;

class SchemaConfigurator implements SchemaConfiguratorInterface
{
    public const UUID_COLUMN = 'uuid';
    public const NAME_COLUMN = 'name';
    public const CONSTRAINT_COLUMN = 'constraint_query';

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

        $table->addColumn(self::CONSTRAINT_COLUMN, Types::TEXT)
            ->setLength(MySqlPlatform::LENGTH_LIMIT_TEXT + 1)
            ->setNotnull(false);

        $table->setPrimaryKey([self::UUID_COLUMN]);
        $table->addUniqueIndex([self::UUID_COLUMN, self::NAME_COLUMN]);

        return $table;
    }
}

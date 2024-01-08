<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SavedSearches\Doctrine;

use CultuurNet\UDB3\Doctrine\DBAL\SchemaConfiguratorInterface;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;

class SchemaConfigurator implements SchemaConfiguratorInterface
{
    public const ID = 'id';
    public const USER = 'user_id';
    public const NAME = 'name';
    public const QUERY = 'query';

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

        $table->addColumn(self::ID, Types::GUID)
            ->setLength(36)
            ->setNotnull(true);

        $table->addColumn(self::USER, Types::GUID)
            ->setLength(36)
            ->setNotnull(true);

        $table->addColumn(self::NAME, Types::STRING)
            ->setLength(255)
            ->setNotnull(true);

        $table->addColumn(self::QUERY, Types::TEXT)
            ->setNotnull(true);

        $table->addIndex([self::ID]);
        $table->addIndex([self::USER]);

        return $table;
    }
}

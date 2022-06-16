<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\Relations\Repository\Doctrine;

use CultuurNet\UDB3\Doctrine\DBAL\SchemaConfiguratorInterface;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;

class SchemaConfigurator implements SchemaConfiguratorInterface
{
    public const LABEL_NAME = 'labelName';
    public const RELATION_TYPE = 'relationType';
    public const RELATION_ID = 'relationId';
    public const IMPORTED = 'imported';

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

        $table->addColumn(self::LABEL_NAME, Type::STRING)
            ->setLength(255)
            ->setNotnull(true);

        $table->addColumn(self::RELATION_TYPE, Type::STRING)
            ->setLength(255)
            ->setNotnull(true);

        $table->addColumn(self::RELATION_ID, Type::GUID)
            ->setLength(36)
            ->setNotnull(true);

        $table->addColumn(self::IMPORTED, Type::BOOLEAN)
            ->setNotnull(true)
            ->setDefault(false);

        $table->addIndex([self::LABEL_NAME]);
        $table->addUniqueIndex([
            self::LABEL_NAME,
            self::RELATION_TYPE,
            self::RELATION_ID,
        ]);

        return $table;
    }
}

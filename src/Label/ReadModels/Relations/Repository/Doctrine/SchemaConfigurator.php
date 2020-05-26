<?php

namespace CultuurNet\UDB3\Label\ReadModels\Relations\Repository\Doctrine;

use CultuurNet\UDB3\Doctrine\DBAL\SchemaConfiguratorInterface;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use ValueObjects\StringLiteral\StringLiteral;

class SchemaConfigurator implements SchemaConfiguratorInterface
{
    const LABEL_NAME = 'labelName';
    const RELATION_TYPE = 'relationType';
    const RELATION_ID = 'relationId';
    const IMPORTED = 'imported';

    /**
     * @var StringLiteral
     */
    private $tableName;

    /**
     * SchemaConfigurator constructor.
     * @param StringLiteral $tableName
     */
    public function __construct(StringLiteral $tableName)
    {
        $this->tableName = $tableName;
    }

    /**
     * @param AbstractSchemaManager $schemaManager
     */
    public function configure(AbstractSchemaManager $schemaManager)
    {
        $schema = $schemaManager->createSchema();

        if (!$schema->hasTable($this->tableName->toNative())) {
            $table = $this->createTable($schema, $this->tableName);

            $schemaManager->createTable($table);
        }
    }

    /**
     * @param Schema $schema
     * @param StringLiteral $tableName
     * @return \Doctrine\DBAL\Schema\Table
     */
    private function createTable(Schema $schema, StringLiteral $tableName)
    {
        $table = $schema->createTable($tableName->toNative());

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

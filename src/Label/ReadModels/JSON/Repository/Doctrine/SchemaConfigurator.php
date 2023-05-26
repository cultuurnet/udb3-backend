<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Doctrine;

use CultuurNet\UDB3\Doctrine\DBAL\SchemaConfiguratorInterface;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;
use CultuurNet\UDB3\StringLiteral;

class SchemaConfigurator implements SchemaConfiguratorInterface
{
    public const UUID_COLUMN = 'uuid_col';
    public const NAME_COLUMN = 'name';
    public const VISIBLE_COLUMN = 'visible';
    public const PRIVATE_COLUMN = 'private';
    public const PARENT_UUID_COLUMN = 'parentUuid';
    public const COUNT_COLUMN = 'count_col';

    public const EXCLUDED_COLUMN = 'excluded';

    /**
     * @var StringLiteral
     */
    private $tableName;


    public function __construct(StringLiteral $tableName)
    {
        $this->tableName = $tableName;
    }

    /**
     * @inheritdoc
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
     * @return \Doctrine\DBAL\Schema\Table
     */
    private function createTable(Schema $schema, StringLiteral $tableName)
    {
        $table = $schema->createTable($tableName->toNative());

        $table->addColumn(self::UUID_COLUMN, Type::GUID)
            ->setLength(36)
            ->setNotnull(true);

        $table->addColumn(self::NAME_COLUMN, Type::STRING)
            ->setLength(255)
            ->setNotnull(true);

        $table->addColumn(self::VISIBLE_COLUMN, Type::BOOLEAN)
            ->setNotnull(true)
            ->setDefault(true);

        $table->addColumn(self::PRIVATE_COLUMN, Type::BOOLEAN)
            ->setNotnull(true)
            ->setDefault(false);

        $table->addColumn(self::PARENT_UUID_COLUMN, Type::GUID)
            ->setLength(36)
            ->setNotnull(false);

        $table->addColumn(self::COUNT_COLUMN, Type::BIGINT)
            ->setNotnull(true)
            ->setDefault(0);

        $table->addColumn(self::EXCLUDED_COLUMN, Type::BOOLEAN)
            ->setNotnull(true)
            ->setDefault(false);

        $table->setPrimaryKey([self::UUID_COLUMN]);
        $table->addUniqueIndex([self::UUID_COLUMN]);
        $table->addUniqueIndex([self::NAME_COLUMN]);

        return $table;
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\SavedSearches\Doctrine;

use CultuurNet\UDB3\Doctrine\DBAL\SchemaConfiguratorInterface;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use ValueObjects\StringLiteral\StringLiteral;

class SchemaConfigurator implements SchemaConfiguratorInterface
{
    public const ID = 'id';
    public const USER = 'user_id';
    public const NAME = 'name';
    public const QUERY = 'query';

    /**
     * @var StringLiteral
     */
    private $tableName;

    /**
     * SchemaConfigurator constructor.
     */
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
     * @return Table
     */
    private function createTable(Schema $schema, StringLiteral $tableName)
    {
        $table = $schema->createTable($tableName->toNative());

        $table->addColumn(self::ID, Type::GUID)
            ->setLength(36)
            ->setNotnull(true);

        $table->addColumn(self::USER, Type::GUID)
            ->setLength(36)
            ->setNotnull(true);

        $table->addColumn(self::NAME, Type::STRING)
            ->setLength(255)
            ->setNotnull(true);

        $table->addColumn(self::QUERY, Type::TEXT)
            ->setNotnull(true);

        $table->addIndex([self::ID]);
        $table->addIndex([self::USER]);

        return $table;
    }
}

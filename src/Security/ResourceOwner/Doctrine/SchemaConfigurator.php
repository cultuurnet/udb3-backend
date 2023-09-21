<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security\ResourceOwner\Doctrine;

use CultuurNet\UDB3\Doctrine\DBAL\SchemaConfiguratorInterface;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use CultuurNet\UDB3\StringLiteral;

class SchemaConfigurator implements SchemaConfiguratorInterface
{
    protected string $tableName;

    protected string $idField;

    public function __construct(string $tableName, string $idField)
    {
        $this->tableName = $tableName;
        $this->idField = $idField;
    }

    public function configure(AbstractSchemaManager $schemaManager): void
    {
        $schema = $schemaManager->createSchema();
        $table = $schema->createTable($this->tableName);

        $table->addColumn(
            $this->idField,
            'guid',
            ['length' => 36, 'notnull' => true]
        );
        $table->addColumn(
            'user_id',
            'guid',
            ['length' => 36, 'notnull' => true]
        );

        $table->setPrimaryKey([$this->idField, 'user_id']);

        $schemaManager->createTable($table);
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security\ResourceOwner\Doctrine;

use CultuurNet\UDB3\Doctrine\DBAL\SchemaConfiguratorInterface;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use CultuurNet\UDB3\StringLiteral;

class SchemaConfigurator implements SchemaConfiguratorInterface
{
    protected StringLiteral $tableName;

    protected StringLiteral $idField;

    public function __construct(StringLiteral $tableName, StringLiteral $idField)
    {
        $this->tableName = $tableName;
        $this->idField = $idField;
    }

    public function configure(AbstractSchemaManager $schemaManager): void
    {
        $schema = $schemaManager->createSchema();
        $table = $schema->createTable($this->tableName->toNative());

        $table->addColumn(
            $this->idField->toNative(),
            'guid',
            ['length' => 36, 'notnull' => true]
        );
        $table->addColumn(
            'user_id',
            'guid',
            ['length' => 36, 'notnull' => true]
        );

        $table->setPrimaryKey([$this->idField->toNative(), 'user_id']);

        $schemaManager->createTable($table);
    }
}

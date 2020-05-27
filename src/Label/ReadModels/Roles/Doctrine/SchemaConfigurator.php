<?php

namespace CultuurNet\UDB3\Label\ReadModels\Roles\Doctrine;

use CultuurNet\UDB3\Doctrine\DBAL\SchemaConfiguratorInterface;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Types\Type;
use ValueObjects\StringLiteral\StringLiteral;

class SchemaConfigurator implements SchemaConfiguratorInterface
{
    const LABEL_ID_COLUMN = 'label_id';
    const ROLE_ID_COLUMN = 'role_id';

    /**
     * @var StringLiteral
     */
    private $labelRolesTableName;

    /**
     * SchemaConfigurator constructor.
     * @param StringLiteral $labelRolesTableName
     */
    public function __construct(StringLiteral $labelRolesTableName)
    {
        $this->labelRolesTableName = $labelRolesTableName;
    }

    public function configure(AbstractSchemaManager $schemaManager)
    {
        $schema = $schemaManager->createSchema();

        if (!$schema->hasTable((string) $this->labelRolesTableName)) {
            $labelRolesTable = $schema->createTable(
                $this->labelRolesTableName->toNative()
            );

            $labelRolesTable->addColumn(self::LABEL_ID_COLUMN, Type::GUID)
                ->setLength(36)
                ->setNotnull(true);

            $labelRolesTable->addColumn(self::ROLE_ID_COLUMN, Type::GUID)
                ->setLength(36)
                ->setNotnull(true);


            $labelRolesTable->setPrimaryKey([
                self::LABEL_ID_COLUMN,
                self::ROLE_ID_COLUMN,
            ]);

            $schemaManager->createTable($labelRolesTable);
        }
    }
}

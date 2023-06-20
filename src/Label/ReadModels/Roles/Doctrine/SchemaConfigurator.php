<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\Roles\Doctrine;

use CultuurNet\UDB3\Doctrine\DBAL\SchemaConfiguratorInterface;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Types\Type;

class SchemaConfigurator implements SchemaConfiguratorInterface
{
    public const LABEL_ID_COLUMN = 'label_id';
    public const ROLE_ID_COLUMN = 'role_id';

    private string $labelRolesTableName;
    
    public function __construct(string $labelRolesTableName)
    {
        $this->labelRolesTableName = $labelRolesTableName;
    }

    public function configure(AbstractSchemaManager $schemaManager)
    {
        $schema = $schemaManager->createSchema();

        if (!$schema->hasTable($this->labelRolesTableName)) {
            $labelRolesTable = $schema->createTable(
                $this->labelRolesTableName
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

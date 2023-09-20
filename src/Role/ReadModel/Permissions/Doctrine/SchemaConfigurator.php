<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\ReadModel\Permissions\Doctrine;

use CultuurNet\UDB3\Doctrine\DBAL\SchemaConfiguratorInterface;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Types\Type;

class SchemaConfigurator implements SchemaConfiguratorInterface
{
    public const USER_ID_COLUMN = 'user_id';
    public const ROLE_ID_COLUMN = 'role_id';
    public const PERMISSION_COLUMN = 'permission';

    private string $userRoleTableName;

    private string $rolePermissionTableName;

    public function __construct(string $userRoleTableName, string $rolePermissionTableName)
    {
        $this->userRoleTableName = $userRoleTableName;
        $this->rolePermissionTableName = $rolePermissionTableName;
    }

    public function configure(AbstractSchemaManager $schemaManager): void
    {
        $schema = $schemaManager->createSchema();

        if (!$schema->hasTable($this->userRoleTableName)) {
            $userRoleTable = $schema->createTable($this->userRoleTableName);

            $userRoleTable->addColumn(self::USER_ID_COLUMN, Type::GUID)
                ->setLength(36)
                ->setNotnull(true);

            $userRoleTable->addColumn(self::ROLE_ID_COLUMN, Type::GUID)
                ->setLength(36)
                ->setNotnull(true);


            $userRoleTable->setPrimaryKey([self::USER_ID_COLUMN, self::ROLE_ID_COLUMN]);

            $schemaManager->createTable($userRoleTable);
        }

        if (!$schema->hasTable($this->rolePermissionTableName)) {
            $rolePermissionTable = $schema->createTable($this->rolePermissionTableName);

            $rolePermissionTable->addColumn(self::ROLE_ID_COLUMN, Type::GUID)
                ->setLength(36)
                ->setNotnull(true);

            $rolePermissionTable->addColumn(self::PERMISSION_COLUMN, Type::STRING)
                ->setLength(255)
                ->setNotnull(true);

            $rolePermissionTable->setPrimaryKey([self::ROLE_ID_COLUMN, self::PERMISSION_COLUMN]);

            $schemaManager->createTable($rolePermissionTable);
        }
    }
}

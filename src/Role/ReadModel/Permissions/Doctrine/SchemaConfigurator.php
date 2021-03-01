<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\ReadModel\Permissions\Doctrine;

use CultuurNet\UDB3\Doctrine\DBAL\SchemaConfiguratorInterface;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Types\Type;
use ValueObjects\StringLiteral\StringLiteral;

class SchemaConfigurator implements SchemaConfiguratorInterface
{
    public const USER_ID_COLUMN = 'user_id';
    public const ROLE_ID_COLUMN = 'role_id';
    public const PERMISSION_COLUMN = 'permission';

    /**
     * @var StringLiteral
     */
    private $userRoleTableName;

    /**
     * @var StringLiteral
     */
    private $rolePermissionTableName;

    /**
     * SchemaConfigurator constructor.
     */
    public function __construct(StringLiteral $userRoleTableName, StringLiteral $rolePermissionTableName)
    {
        $this->userRoleTableName = $userRoleTableName;
        $this->rolePermissionTableName = $rolePermissionTableName;
    }


    public function configure(AbstractSchemaManager $schemaManager)
    {
        $schema = $schemaManager->createSchema();

        if (!$schema->hasTable((string) $this->userRoleTableName)) {
            $userRoleTable = $schema->createTable((string) $this->userRoleTableName);

            $userRoleTable->addColumn(self::USER_ID_COLUMN, Type::GUID)
                ->setLength(36)
                ->setNotnull(true);

            $userRoleTable->addColumn(self::ROLE_ID_COLUMN, Type::GUID)
                ->setLength(36)
                ->setNotnull(true);


            $userRoleTable->setPrimaryKey([self::USER_ID_COLUMN, self::ROLE_ID_COLUMN]);

            $schemaManager->createTable($userRoleTable);
        }

        if (!$schema->hasTable((string) $this->rolePermissionTableName)) {
            $rolePermissionTable = $schema->createTable((string) $this->rolePermissionTableName);

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

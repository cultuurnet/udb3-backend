<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\ReadModel\Permissions\Doctrine;

use CultuurNet\UDB3\Role\ReadModel\Permissions\UserPermissionsWriteRepositoryInterface;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use Doctrine\DBAL\Connection;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

class UserPermissionsWriteRepository implements UserPermissionsWriteRepositoryInterface
{
    private Connection $connection;

    private StringLiteral $userRoleTableName;

    private StringLiteral $rolePermissionTableName;

    public function __construct(
        Connection $connection,
        StringLiteral $userRoleTableName,
        StringLiteral $rolePermissionTableName
    ) {
        $this->connection = $connection;
        $this->userRoleTableName = $userRoleTableName;
        $this->rolePermissionTableName = $rolePermissionTableName;
    }

    /**
     * @inheritdoc
     */
    public function removeRole(UUID $roleId): void
    {
        $connection = $this->connection;

        try {
            $connection->beginTransaction();

            $connection->delete(
                $this->userRoleTableName,
                [SchemaConfigurator::ROLE_ID_COLUMN => (string)$roleId]
            );

            $connection->delete(
                $this->rolePermissionTableName,
                [SchemaConfigurator::ROLE_ID_COLUMN => (string)$roleId]
            );

            $connection->commit();
        } catch (\Exception $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
    }

    /**
     * @inheritdoc
     */
    public function addRolePermission(UUID $roleId, Permission $permission): void
    {
        $this->connection->insert(
            $this->rolePermissionTableName,
            [
                SchemaConfigurator::ROLE_ID_COLUMN => (string) $roleId,
                SchemaConfigurator::PERMISSION_COLUMN => $permission->toString(),
            ]
        );
    }

    /**
     * @inheritdoc
     */
    public function removeRolePermission(UUID $roleId, Permission $permission): void
    {
        $this->connection->delete(
            $this->rolePermissionTableName,
            [
                SchemaConfigurator::ROLE_ID_COLUMN => (string) $roleId,
                SchemaConfigurator::PERMISSION_COLUMN => $permission->toString(),
            ]
        );
    }

    /**
     * @inheritdoc
     */
    public function addUserRole(StringLiteral $userId, UUID $roleId): void
    {
        $this->connection->insert(
            $this->userRoleTableName,
            [
                SchemaConfigurator::ROLE_ID_COLUMN => (string) $roleId,
                SchemaConfigurator::USER_ID_COLUMN => (string) $userId,
            ]
        );
    }

    /**
     * @inheritdoc
     */
    public function removeUserRole(StringLiteral $userId, UUID $roleId): void
    {
        $this->connection->delete(
            $this->userRoleTableName,
            [
                SchemaConfigurator::USER_ID_COLUMN => (string) $userId,
                SchemaConfigurator::ROLE_ID_COLUMN => (string) $roleId,
            ]
        );
    }
}

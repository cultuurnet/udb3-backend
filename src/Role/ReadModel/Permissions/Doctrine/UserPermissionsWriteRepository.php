<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\ReadModel\Permissions\Doctrine;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Role\ReadModel\Permissions\UserPermissionsWriteRepositoryInterface;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use Doctrine\DBAL\Connection;

class UserPermissionsWriteRepository implements UserPermissionsWriteRepositoryInterface
{
    private Connection $connection;

    private string $userRoleTableName;

    private string $rolePermissionTableName;

    public function __construct(
        Connection $connection,
        string $userRoleTableName,
        string $rolePermissionTableName
    ) {
        $this->connection = $connection;
        $this->userRoleTableName = $userRoleTableName;
        $this->rolePermissionTableName = $rolePermissionTableName;
    }

    public function removeRole(UUID $roleId): void
    {
        $connection = $this->connection;

        try {
            $connection->beginTransaction();

            $connection->delete(
                $this->userRoleTableName,
                [ColumnNames::ROLE_ID_COLUMN => $roleId->toString()]
            );

            $connection->delete(
                $this->rolePermissionTableName,
                [ColumnNames::ROLE_ID_COLUMN => $roleId->toString()]
            );

            $connection->commit();
        } catch (\Exception $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
    }

    public function addRolePermission(UUID $roleId, Permission $permission): void
    {
        $this->connection->insert(
            $this->rolePermissionTableName,
            [
                ColumnNames::ROLE_ID_COLUMN => $roleId->toString(),
                ColumnNames::PERMISSION_COLUMN => $permission->toString(),
            ]
        );
    }

    public function removeRolePermission(UUID $roleId, Permission $permission): void
    {
        $this->connection->delete(
            $this->rolePermissionTableName,
            [
                ColumnNames::ROLE_ID_COLUMN => $roleId->toString(),
                ColumnNames::PERMISSION_COLUMN => $permission->toString(),
            ]
        );
    }

    public function addUserRole(string $userId, UUID $roleId): void
    {
        $this->connection->insert(
            $this->userRoleTableName,
            [
                ColumnNames::ROLE_ID_COLUMN => $roleId->toString(),
                ColumnNames::USER_ID_COLUMN => $userId,
            ]
        );
    }

    public function removeUserRole(string $userId, UUID $roleId): void
    {
        $this->connection->delete(
            $this->userRoleTableName,
            [
                ColumnNames::USER_ID_COLUMN => $userId,
                ColumnNames::ROLE_ID_COLUMN => $roleId->toString(),
            ]
        );
    }
}

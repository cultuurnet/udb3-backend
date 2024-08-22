<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\ReadModel\Permissions\Doctrine;

use CultuurNet\UDB3\Role\ReadModel\Permissions\UserPermissionsReadRepositoryInterface;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use Doctrine\DBAL\Connection;

class UserPermissionsReadRepository implements UserPermissionsReadRepositoryInterface
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

    /**
     * @return Permission[]
     */
    public function getPermissions(string $userId): array
    {
        $userRoleQuery = $this->connection->createQueryBuilder()
            ->select(ColumnNames::ROLE_ID_COLUMN)
            ->from($this->userRoleTableName)
            ->where(ColumnNames::USER_ID_COLUMN . ' = :userId');

        $userPermissionQuery = $this->connection->createQueryBuilder()
            ->select('DISTINCT ' . ColumnNames::PERMISSION_COLUMN)
            ->from($this->rolePermissionTableName, 'rp')
            ->innerJoin(
                'rp',
                sprintf('(%s)', $userRoleQuery->getSQL()),
                'up',
                'rp.' . ColumnNames::ROLE_ID_COLUMN . ' = up.' . ColumnNames::ROLE_ID_COLUMN
            )
            ->setParameter('userId', $userId);

        $results = $userPermissionQuery->execute()->fetchFirstColumn();

        return array_map(
            fn (string $permission) => new Permission($permission),
            $results
        );
    }

    public function hasPermission(string $userId, Permission $permission): bool
    {
        $userRoleQuery = $this->connection->createQueryBuilder()
            ->select(ColumnNames::ROLE_ID_COLUMN)
            ->from($this->userRoleTableName)
            ->where(ColumnNames::USER_ID_COLUMN . ' = :userId');

        $userPermissionQuery = $this->connection->createQueryBuilder()
            ->select('DISTINCT ' . ColumnNames::PERMISSION_COLUMN)
            ->from($this->rolePermissionTableName, 'rp')
            ->andWhere(ColumnNames::PERMISSION_COLUMN . ' = :permission')
            ->innerJoin(
                'rp',
                sprintf('(%s)', $userRoleQuery->getSQL()),
                'up',
                'rp.' . ColumnNames::ROLE_ID_COLUMN . ' = up.' . ColumnNames::ROLE_ID_COLUMN
            )
            ->setParameter('userId', $userId)
            ->setParameter('permission', $permission->toString());

        $results = $userPermissionQuery->execute()->fetchFirstColumn();
        return count($results) > 0;
    }
}

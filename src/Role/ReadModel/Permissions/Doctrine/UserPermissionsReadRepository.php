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
            ->select(SchemaConfigurator::ROLE_ID_COLUMN)
            ->from($this->userRoleTableName)
            ->where(SchemaConfigurator::USER_ID_COLUMN . ' = :userId');

        $userPermissionQuery = $this->connection->createQueryBuilder()
            ->select('DISTINCT ' . SchemaConfigurator::PERMISSION_COLUMN)
            ->from($this->rolePermissionTableName, 'rp')
            ->innerJoin(
                'rp',
                sprintf('(%s)', $userRoleQuery->getSQL()),
                'up',
                'rp.' . SchemaConfigurator::ROLE_ID_COLUMN . ' = up.' . SchemaConfigurator::ROLE_ID_COLUMN
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
            ->select(SchemaConfigurator::ROLE_ID_COLUMN)
            ->from($this->userRoleTableName)
            ->where(SchemaConfigurator::USER_ID_COLUMN . ' = :userId');

        $userPermissionQuery = $this->connection->createQueryBuilder()
            ->select('DISTINCT ' . SchemaConfigurator::PERMISSION_COLUMN)
            ->from($this->rolePermissionTableName, 'rp')
            ->andWhere(SchemaConfigurator::PERMISSION_COLUMN . ' = :permission')
            ->innerJoin(
                'rp',
                sprintf('(%s)', $userRoleQuery->getSQL()),
                'up',
                'rp.' . SchemaConfigurator::ROLE_ID_COLUMN . ' = up.' . SchemaConfigurator::ROLE_ID_COLUMN
            )
            ->setParameter('userId', $userId)
            ->setParameter('permission', $permission->toString());

        $results = $userPermissionQuery->execute()->fetchFirstColumn();
        return count($results) > 0;
    }
}

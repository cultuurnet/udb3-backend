<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\ReadModel\Constraints\Doctrine;

use CultuurNet\UDB3\Role\ReadModel\Constraints\UserConstraintsReadRepositoryInterface;
use CultuurNet\UDB3\Role\ReadModel\Permissions\Doctrine\ColumnNames as PermissionsSchemaConfigurator;
use CultuurNet\UDB3\Role\ReadModel\Search\Doctrine\SchemaConfigurator as SearchSchemaConfigurator;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use Doctrine\DBAL\Connection;

class UserConstraintsReadRepository implements UserConstraintsReadRepositoryInterface
{
    private Connection $connection;

    private string $userRolesTableName;

    private string $rolePermissionsTableName;

    private string $rolesSearchTableName;

    public function __construct(
        Connection $connection,
        string $userRolesTableName,
        string $rolePermissionsTableName,
        string $rolesSearchTableName
    ) {
        $this->connection = $connection;
        $this->userRolesTableName = $userRolesTableName;
        $this->rolePermissionsTableName = $rolePermissionsTableName;
        $this->rolesSearchTableName = $rolesSearchTableName;
    }

    /**
     * @return string[]
     */
    public function getByUserAndPermission(
        string $userId,
        Permission $permission
    ): array {
        $userRolesSubQuery = $this->connection->createQueryBuilder()
            ->select(PermissionsSchemaConfigurator::ROLE_ID_COLUMN)
            ->from($this->userRolesTableName)
            ->where(PermissionsSchemaConfigurator::USER_ID_COLUMN . ' = :userId');

        $queryBuilder = $this->connection->createQueryBuilder();
        $userConstraintsQuery = $queryBuilder
            ->select('rs.' . SearchSchemaConfigurator::CONSTRAINT_COLUMN)
            ->from($this->rolesSearchTableName, 'rs')
            ->innerJoin(
                'rs',
                sprintf('(%s)', $userRolesSubQuery->getSQL()),
                'ur',
                'rs.' . SearchSchemaConfigurator::UUID_COLUMN . ' = ur.' . PermissionsSchemaConfigurator::ROLE_ID_COLUMN
            )
            ->innerJoin(
                'rs',
                $this->rolePermissionsTableName,
                'rp',
                'rs.' . SearchSchemaConfigurator::UUID_COLUMN . ' = rp.' . PermissionsSchemaConfigurator::ROLE_ID_COLUMN
            )
            ->where(PermissionsSchemaConfigurator::PERMISSION_COLUMN . ' = :permission')
            ->andWhere($queryBuilder->expr()->isNotNull(
                'rs.' . SearchSchemaConfigurator::CONSTRAINT_COLUMN
            ))
            ->setParameter('userId', $userId)
            ->setParameter('permission', $permission->toString());

        return $userConstraintsQuery->execute()->fetchFirstColumn();
    }
}

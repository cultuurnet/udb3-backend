<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\ReadModel\Constraints\Doctrine;

use CultuurNet\UDB3\Role\ReadModel\Constraints\UserConstraintsReadRepositoryInterface;
use CultuurNet\UDB3\Role\ReadModel\Permissions\Doctrine\ColumnNames as PermissionsColumnNames;
use CultuurNet\UDB3\Role\ReadModel\Search\Doctrine\ColumnNames as SearchColumnNames;
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
            ->select(PermissionsColumnNames::ROLE_ID_COLUMN)
            ->from($this->userRolesTableName)
            ->where(PermissionsColumnNames::USER_ID_COLUMN . ' = :userId');

        $queryBuilder = $this->connection->createQueryBuilder();
        $userConstraintsQuery = $queryBuilder
            ->select('rs.' . SearchColumnNames::CONSTRAINT_COLUMN)
            ->from($this->rolesSearchTableName, 'rs')
            ->innerJoin(
                'rs',
                sprintf('(%s)', $userRolesSubQuery->getSQL()),
                'ur',
                'rs.' . SearchColumnNames::UUID_COLUMN . ' = ur.' . PermissionsColumnNames::ROLE_ID_COLUMN
            )
            ->innerJoin(
                'rs',
                $this->rolePermissionsTableName,
                'rp',
                'rs.' . SearchColumnNames::UUID_COLUMN . ' = rp.' . PermissionsColumnNames::ROLE_ID_COLUMN
            )
            ->where(PermissionsColumnNames::PERMISSION_COLUMN . ' = :permission')
            ->andWhere($queryBuilder->expr()->isNotNull(
                'rs.' . SearchColumnNames::CONSTRAINT_COLUMN
            ))
            ->setParameter('userId', $userId)
            ->setParameter('permission', $permission->toString());

        return $userConstraintsQuery->execute()->fetchFirstColumn();
    }
}

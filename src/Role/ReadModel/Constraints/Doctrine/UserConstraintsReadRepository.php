<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\ReadModel\Constraints\Doctrine;

use CultuurNet\UDB3\Role\ReadModel\Constraints\UserConstraintsReadRepositoryInterface;
use CultuurNet\UDB3\Role\ReadModel\Permissions\Doctrine\SchemaConfigurator as PermissionsSchemaConfigurator;
use CultuurNet\UDB3\Role\ReadModel\Search\Doctrine\SchemaConfigurator as SearchSchemaConfigurator;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use Doctrine\DBAL\Connection;
use CultuurNet\UDB3\StringLiteral;

class UserConstraintsReadRepository implements UserConstraintsReadRepositoryInterface
{
    private Connection $connection;

    private StringLiteral $userRolesTableName;

    private StringLiteral $rolePermissionsTableName;

    private StringLiteral $rolesSearchTableName;

    public function __construct(
        Connection $connection,
        StringLiteral $userRolesTableName,
        StringLiteral $rolePermissionsTableName,
        StringLiteral $rolesSearchTableName
    ) {
        $this->connection = $connection;
        $this->userRolesTableName = $userRolesTableName;
        $this->rolePermissionsTableName = $rolePermissionsTableName;
        $this->rolesSearchTableName = $rolesSearchTableName;
    }

    /**
     * @return StringLiteral[]
     */
    public function getByUserAndPermission(
        StringLiteral $userId,
        Permission $permission
    ): array {
        $userRolesSubQuery = $this->connection->createQueryBuilder()
            ->select(PermissionsSchemaConfigurator::ROLE_ID_COLUMN)
            ->from($this->userRolesTableName->toNative())
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
                $this->rolePermissionsTableName->toNative(),
                'rp',
                'rs.' . SearchSchemaConfigurator::UUID_COLUMN . ' = rp.' . PermissionsSchemaConfigurator::ROLE_ID_COLUMN
            )
            ->where(PermissionsSchemaConfigurator::PERMISSION_COLUMN . ' = :permission')
            ->andWhere($queryBuilder->expr()->isNotNull(
                'rs.' . SearchSchemaConfigurator::CONSTRAINT_COLUMN
            ))
            ->setParameter('userId', $userId->toNative())
            ->setParameter('permission', $permission->toString());

        $results = $userConstraintsQuery->execute()->fetchAll(\PDO::FETCH_COLUMN);

        return array_map(function ($constraint) {
            return new StringLiteral($constraint);
        }, $results);
    }
}

<?php

namespace CultuurNet\UDB3\Role\ReadModel\Constraints\Doctrine;

use CultuurNet\UDB3\Role\ReadModel\Constraints\UserConstraintsReadRepositoryInterface;
use CultuurNet\UDB3\Role\ReadModel\Permissions\Doctrine\SchemaConfigurator as PermissionsSchemaConfigurator;
use CultuurNet\UDB3\Role\ReadModel\Search\Doctrine\SchemaConfigurator as SearchSchemaConfigurator;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use Doctrine\DBAL\Connection;
use ValueObjects\StringLiteral\StringLiteral;

class UserConstraintsReadRepository implements UserConstraintsReadRepositoryInterface
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var StringLiteral
     */
    private $userRolesTableName;

    /**
     * @var StringLiteral
     */
    private $rolePermissionsTableName;

    /**
     * @var StringLiteral
     */
    private $rolesSearchTableName;

    /**
     * UserConstraintsReadRepository constructor.
     * @param Connection $connection
     * @param StringLiteral $userRolesTableName
     * @param StringLiteral $rolePermissionsTableName
     * @param StringLiteral $rolesSearchTableName
     */
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
     * @param StringLiteral $userId
     * @param Permission $permission
     * @return StringLiteral[]
     */
    public function getByUserAndPermission(
        StringLiteral $userId,
        Permission $permission
    ) {
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
            ->setParameter('permission', $permission->toNative());

        $results = $userConstraintsQuery->execute()->fetchAll(\PDO::FETCH_COLUMN);

        return array_map(function ($constraint) {
            return new StringLiteral($constraint);
        }, $results);
    }
}

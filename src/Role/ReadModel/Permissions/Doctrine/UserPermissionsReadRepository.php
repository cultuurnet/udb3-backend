<?php

namespace CultuurNet\UDB3\Role\ReadModel\Permissions\Doctrine;

use CultuurNet\UDB3\Role\ReadModel\Permissions\UserPermissionsReadRepositoryInterface;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use Doctrine\DBAL\Connection;
use ValueObjects\StringLiteral\StringLiteral;

class UserPermissionsReadRepository implements UserPermissionsReadRepositoryInterface
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var StringLiteral
     */
    private $userRoleTableName;

    /**
     * @var StringLiteral
     */
    private $rolePermissionTableName;

    /**
     * UserPermissionsReadRepository constructor.
     * @param Connection $connection
     * @param StringLiteral $userRoleTableName
     * @param StringLiteral $rolePermissionTableName
     */
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
     * @param StringLiteral $userId
     * @return Permission[]
     */
    public function getPermissions(StringLiteral $userId)
    {
        $userRoleQuery = $this->connection->createQueryBuilder()
            ->select(SchemaConfigurator::ROLE_ID_COLUMN)
            ->from((string) $this->userRoleTableName)
            ->where(SchemaConfigurator::USER_ID_COLUMN . ' = :userId');

        $userPermissionQuery = $this->connection->createQueryBuilder()
            ->select('DISTINCT ' . SchemaConfigurator::PERMISSION_COLUMN)
            ->from($this->rolePermissionTableName, 'rp')
            ->innerJoin(
                'rp',
                sprintf('(%s)', $userRoleQuery->getSQL()),
                'up',
                'rp.' . SchemaConfigurator::ROLE_ID_COLUMN .' = up.' . SchemaConfigurator::ROLE_ID_COLUMN
            )
            ->setParameter('userId', (string) $userId);

        $results = $userPermissionQuery->execute()->fetchAll(\PDO::FETCH_COLUMN);

        $permissions = array_map(array(Permission::class, 'fromNative'), $results);

        return $permissions;
    }
}

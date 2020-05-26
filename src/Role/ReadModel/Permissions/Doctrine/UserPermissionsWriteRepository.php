<?php

namespace CultuurNet\UDB3\Role\ReadModel\Permissions\Doctrine;

use CultuurNet\UDB3\Role\ReadModel\Permissions\UserPermissionsWriteRepositoryInterface;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use Doctrine\DBAL\Connection;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

class UserPermissionsWriteRepository implements UserPermissionsWriteRepositoryInterface
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
     * UserPermissionsWriteRepository constructor.
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
     * @inheritdoc
     */
    public function removeRole(UUID $roleId)
    {
        $connection = $this->connection;

        try {
            $connection->beginTransaction();

            $connection->delete(
                $this->userRoleTableName,
                array(SchemaConfigurator::ROLE_ID_COLUMN => (string)$roleId)
            );

            $connection->delete(
                $this->rolePermissionTableName,
                array(SchemaConfigurator::ROLE_ID_COLUMN => (string)$roleId)
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
    public function addRolePermission(UUID $roleId, Permission $permission)
    {
        $this->connection->insert(
            $this->rolePermissionTableName,
            array(
                SchemaConfigurator::ROLE_ID_COLUMN => (string) $roleId,
                SchemaConfigurator::PERMISSION_COLUMN => (string) $permission,
            )
        );
    }

    /**
     * @inheritdoc
     */
    public function removeRolePermission(UUID $roleId, Permission $permission)
    {
        $this->connection->delete(
            $this->rolePermissionTableName,
            array(
                SchemaConfigurator::ROLE_ID_COLUMN => (string) $roleId,
                SchemaConfigurator::PERMISSION_COLUMN => (string) $permission,
            )
        );
    }

    /**
     * @inheritdoc
     */
    public function addUserRole(StringLiteral $userId, UUID $roleId)
    {
        $this->connection->insert(
            $this->userRoleTableName,
            array(
                SchemaConfigurator::ROLE_ID_COLUMN => (string) $roleId,
                SchemaConfigurator::USER_ID_COLUMN => (string) $userId,
            )
        );
    }

    /**
     * @inheritdoc
     */
    public function removeUserRole(StringLiteral $userId, UUID $roleId)
    {
        $this->connection->delete(
            $this->userRoleTableName,
            array(
                SchemaConfigurator::USER_ID_COLUMN => (string) $userId,
                SchemaConfigurator::ROLE_ID_COLUMN => (string) $roleId,
            )
        );
    }
}

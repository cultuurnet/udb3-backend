<?php

namespace CultuurNet\UDB3\Role\ReadModel\Permissions\Doctrine;

use CultuurNet\UDB3\DBALTestConnectionTrait;
use CultuurNet\UDB3\Role\ReadModel\Permissions\UserPermissionsWriteRepositoryInterface;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

class UserPermissionsWriteRepositoryTest extends TestCase
{
    use DBALTestConnectionTrait;

    /**
     * @var UserPermissionsWriteRepositoryInterface
     */
    private $repository;

    /*
    * @var StringLiteral
    */
    private $userRoleTableName;

    /**
     * @var StringLiteral
     */
    private $rolePermissionTableName;

    public function setUp()
    {
        $this->userRoleTableName = new StringLiteral('user_role');
        $this->rolePermissionTableName = new StringLiteral('role_permission');

        $schemaConfigurator = new SchemaConfigurator(
            $this->userRoleTableName,
            $this->rolePermissionTableName
        );

        $schemaManager = $this->getConnection()->getSchemaManager();

        $schemaConfigurator->configure($schemaManager);

        $this->repository = new UserPermissionsWriteRepository(
            $this->getConnection(),
            $this->userRoleTableName,
            $this->rolePermissionTableName
        );
    }

    /**
     * @test
     */
    public function it_should_update_permissions_when_a_user_is_assigned_a_role()
    {
        $userId = new StringLiteral('4A9F8064-755E-46C5-A5C2-DFD7970A4BF3');
        $roleId = new UUID();

        $this->repository->addUserRole($userId, $roleId);

        $rows = $this->getTableRows($this->userRoleTableName);

        $expectedRows = [
            [
                SchemaConfigurator::USER_ID_COLUMN => $userId,
                SchemaConfigurator::ROLE_ID_COLUMN => (string) $roleId,
            ],
        ];

        $this->assertEquals($expectedRows, $rows);
    }

    /**
     * @test
     */
    public function it_should_update_permissions_when_a_role_is_assigned_permissions()
    {
        $roleId = new UUID();
        $permission = Permission::get(Permission::LABELS_BEHEREN);

        $this->repository->addRolePermission($roleId, $permission);

        $rows = $this->getTableRows($this->rolePermissionTableName);

        $expectedRows = [
            [
                SchemaConfigurator::ROLE_ID_COLUMN => (string) $roleId,
                SchemaConfigurator::PERMISSION_COLUMN => (string) $permission,
            ],
        ];

        $this->assertEquals($expectedRows, $rows);
    }

    /**
     * @test
     */
    public function it_should_revoke_permissions_when_a_role_permission_is_removed()
    {
        $roleId = new UUID();
        $permission = Permission::get(Permission::GEBRUIKERS_BEHEREN);
        $otherRoleId = UUID::generateAsString();

        $this->getConnection()->insert(
            $this->rolePermissionTableName,
            array(
                SchemaConfigurator::ROLE_ID_COLUMN => (string) $roleId,
                SchemaConfigurator::PERMISSION_COLUMN => Permission::LABELS_BEHEREN,
            )
        );

        $this->getConnection()->insert(
            $this->rolePermissionTableName,
            array(
                SchemaConfigurator::ROLE_ID_COLUMN => (string) $roleId,
                SchemaConfigurator::PERMISSION_COLUMN => Permission::GEBRUIKERS_BEHEREN,
            )
        );

        $this->getConnection()->insert(
            $this->rolePermissionTableName,
            array(
                SchemaConfigurator::ROLE_ID_COLUMN => $otherRoleId,
                SchemaConfigurator::PERMISSION_COLUMN => Permission::GEBRUIKERS_BEHEREN,
            )
        );

        $this->repository->removeRolePermission($roleId, $permission);

        $rows = $this->getTableRows($this->rolePermissionTableName);

        $expectedRows = [
            [
                SchemaConfigurator::ROLE_ID_COLUMN => (string) $roleId,
                SchemaConfigurator::PERMISSION_COLUMN => Permission::LABELS_BEHEREN,
            ],
            [
                SchemaConfigurator::ROLE_ID_COLUMN => $otherRoleId,
                SchemaConfigurator::PERMISSION_COLUMN => Permission::GEBRUIKERS_BEHEREN,
            ],
        ];

        $this->assertEquals($expectedRows, $rows);
    }

    /**
     * @test
     */
    public function it_should_revoke_permissions_when_a_role_is_removed()
    {
        $roleId = new UUID();
        $otherRoleId = UUID::generateAsString();
        $userId = UUID::generateAsString();

        $this->getConnection()->insert(
            $this->rolePermissionTableName,
            array(
                SchemaConfigurator::ROLE_ID_COLUMN => (string) $roleId,
                SchemaConfigurator::PERMISSION_COLUMN => Permission::LABELS_BEHEREN,
            )
        );

        $this->getConnection()->insert(
            $this->rolePermissionTableName,
            array(
                SchemaConfigurator::ROLE_ID_COLUMN => (string) $roleId,
                SchemaConfigurator::PERMISSION_COLUMN => Permission::GEBRUIKERS_BEHEREN,
            )
        );

        $this->getConnection()->insert(
            $this->rolePermissionTableName,
            array(
                SchemaConfigurator::ROLE_ID_COLUMN => $otherRoleId,
                SchemaConfigurator::PERMISSION_COLUMN => Permission::GEBRUIKERS_BEHEREN,
            )
        );

        $this->getConnection()->insert(
            $this->userRoleTableName,
            array(
                SchemaConfigurator::USER_ID_COLUMN => $userId,
                SchemaConfigurator::ROLE_ID_COLUMN => (string) $roleId,
            )
        );

        $this->repository->removeRole($roleId);

        $rolePermissions = $this->getTableRows($this->rolePermissionTableName);
        $userRoles = $this->getTableRows($this->userRoleTableName);

        $expectedRolePermissions = [
            [
                SchemaConfigurator::ROLE_ID_COLUMN => $otherRoleId,
                SchemaConfigurator::PERMISSION_COLUMN => Permission::GEBRUIKERS_BEHEREN,
            ],
        ];

        $this->assertEquals($expectedRolePermissions, $rolePermissions);
        $this->assertEquals([], $userRoles);
    }

    /**
     * @test
     */
    public function it_should_revoke_permissions_when_a_role_is_taken_from_a_user()
    {
        $userId = new StringLiteral('4A9F8064-755E-46C5-A5C2-DFD7970A4BF3');
        $otherUserId = UUID::generateAsString();
        $roleId = new UUID();

        $this->getConnection()->insert(
            $this->userRoleTableName,
            array(
                SchemaConfigurator::ROLE_ID_COLUMN => (string) $roleId,
                SchemaConfigurator::USER_ID_COLUMN => $otherUserId,
            )
        );

        $this->getConnection()->insert(
            $this->userRoleTableName,
            array(
                SchemaConfigurator::ROLE_ID_COLUMN => (string) $roleId,
                SchemaConfigurator::USER_ID_COLUMN => $userId,
            )
        );

        $this->repository->removeUserRole($userId, $roleId);

        $rows = $this->getTableRows($this->userRoleTableName);


        $expectedRows = [
            [
                SchemaConfigurator::USER_ID_COLUMN => $otherUserId,
                SchemaConfigurator::ROLE_ID_COLUMN => (string) $roleId,
            ],
        ];

        $this->assertEquals($expectedRows, $rows);
    }

    /**
     * @param $tableName
     * @return array
     */
    private function getTableRows($tableName)
    {
        $sql = 'SELECT * FROM ' . $tableName;

        $statement = $this->getConnection()->executeQuery($sql);
        $rows = $statement->fetchAll(\PDO::FETCH_ASSOC);

        return $rows;
    }
}

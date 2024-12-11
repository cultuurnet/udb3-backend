<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\ReadModel\Permissions\Doctrine;

use CultuurNet\UDB3\DBALTestConnectionTrait;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Role\ReadModel\Permissions\UserPermissionsWriteRepositoryInterface;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\TestCase;

class UserPermissionsWriteRepositoryTest extends TestCase
{
    use DBALTestConnectionTrait;

    private UserPermissionsWriteRepositoryInterface $repository;

    private string $userRoleTableName;

    private string $rolePermissionTableName;

    public function setUp(): void
    {
        $this->setUpDatabase();

        $this->userRoleTableName = 'user_roles';
        $this->rolePermissionTableName = 'role_permissions';

        $this->repository = new UserPermissionsWriteRepository(
            $this->getConnection(),
            $this->userRoleTableName,
            $this->rolePermissionTableName,
        );
    }

    /**
     * @test
     */
    public function it_should_update_permissions_when_a_user_is_assigned_a_role(): void
    {
        $userId = '4A9F8064-755E-46C5-A5C2-DFD7970A4BF3';
        $roleId = new Uuid('ec129012-0301-426b-afc8-a8da7009b82d');

        $this->repository->addUserRole($userId, $roleId);

        $rows = $this->getTableRows($this->userRoleTableName);

        $expectedRows = [
            [
                ColumnNames::USER_ID_COLUMN => $userId,
                ColumnNames::ROLE_ID_COLUMN => $roleId->toString(),
            ],
        ];

        $this->assertEquals($expectedRows, $rows);
    }

    /**
     * @test
     */
    public function it_should_update_permissions_when_a_role_is_assigned_permissions(): void
    {
        $roleId = new Uuid('f2aa9861-ac6f-4def-b462-4e51f250a15a');
        $permission = Permission::labelsBeheren();

        $this->repository->addRolePermission($roleId, $permission);

        $rows = $this->getTableRows($this->rolePermissionTableName);

        $expectedRows = [
            [
                ColumnNames::ROLE_ID_COLUMN => $roleId->toString(),
                ColumnNames::PERMISSION_COLUMN => $permission->toString(),
            ],
        ];

        $this->assertEquals($expectedRows, $rows);
    }

    /**
     * @test
     */
    public function it_should_revoke_permissions_when_a_role_permission_is_removed(): void
    {
        $roleId = new Uuid('5a95c0e1-1194-46b5-9cc3-3354d98763e6');
        $permission = Permission::gebruikersBeheren();
        $otherRoleId = 'ae20f4d4-ee6f-421e-93d2-ac08127b47b3';

        $this->getConnection()->insert(
            $this->rolePermissionTableName,
            [
                ColumnNames::ROLE_ID_COLUMN => $roleId->toString(),
                ColumnNames::PERMISSION_COLUMN => Permission::labelsBeheren()->toString(),
            ]
        );

        $this->getConnection()->insert(
            $this->rolePermissionTableName,
            [
                ColumnNames::ROLE_ID_COLUMN => $roleId->toString(),
                ColumnNames::PERMISSION_COLUMN => Permission::gebruikersBeheren()->toString(),
            ]
        );

        $this->getConnection()->insert(
            $this->rolePermissionTableName,
            [
                ColumnNames::ROLE_ID_COLUMN => $otherRoleId,
                ColumnNames::PERMISSION_COLUMN => Permission::gebruikersBeheren()->toString(),
            ]
        );

        $this->repository->removeRolePermission($roleId, $permission);

        $rows = $this->getTableRows($this->rolePermissionTableName);

        $expectedRows = [
            [
                ColumnNames::ROLE_ID_COLUMN => $roleId->toString(),
                ColumnNames::PERMISSION_COLUMN => Permission::labelsBeheren()->toString(),
            ],
            [
                ColumnNames::ROLE_ID_COLUMN => $otherRoleId,
                ColumnNames::PERMISSION_COLUMN => Permission::gebruikersBeheren()->toString(),
            ],
        ];

        $this->assertEquals($expectedRows, $rows);
    }

    /**
     * @test
     */
    public function it_should_revoke_permissions_when_a_role_is_removed(): void
    {
        $roleId = new Uuid('83345d50-3eac-4c5a-903d-c22ae9c1ff89');
        $otherRoleId = 'b471e5b5-f1ce-4d36-8b3b-72e0982e16c0';
        $userId = '47bb9d17-0117-4a0d-97d5-c74e19e36a7c';

        $this->getConnection()->insert(
            $this->rolePermissionTableName,
            [
                ColumnNames::ROLE_ID_COLUMN => $roleId->toString(),
                ColumnNames::PERMISSION_COLUMN => Permission::labelsBeheren()->toString(),
            ]
        );

        $this->getConnection()->insert(
            $this->rolePermissionTableName,
            [
                ColumnNames::ROLE_ID_COLUMN => $roleId->toString(),
                ColumnNames::PERMISSION_COLUMN => Permission::gebruikersBeheren()->toString(),
            ]
        );

        $this->getConnection()->insert(
            $this->rolePermissionTableName,
            [
                ColumnNames::ROLE_ID_COLUMN => $otherRoleId,
                ColumnNames::PERMISSION_COLUMN => Permission::gebruikersBeheren()->toString(),
            ]
        );

        $this->getConnection()->insert(
            $this->userRoleTableName,
            [
                ColumnNames::USER_ID_COLUMN => $userId,
                ColumnNames::ROLE_ID_COLUMN => $roleId->toString(),
            ]
        );

        $this->repository->removeRole($roleId);

        $rolePermissions = $this->getTableRows($this->rolePermissionTableName);
        $userRoles = $this->getTableRows($this->userRoleTableName);

        $expectedRolePermissions = [
            [
                ColumnNames::ROLE_ID_COLUMN => $otherRoleId,
                ColumnNames::PERMISSION_COLUMN => Permission::gebruikersBeheren()->toString(),
            ],
        ];

        $this->assertEquals($expectedRolePermissions, $rolePermissions);
        $this->assertEquals([], $userRoles);
    }

    /**
     * @test
     */
    public function it_should_revoke_permissions_when_a_role_is_taken_from_a_user(): void
    {
        $userId = '4A9F8064-755E-46C5-A5C2-DFD7970A4BF3';
        $otherUserId = '09c31dcb-2312-4ec7-9c06-10592b5dbf67';
        $roleId = new Uuid('dc0acb4c-309e-47a6-9774-6bf7d7fc2e5d');

        $this->getConnection()->insert(
            $this->userRoleTableName,
            [
                ColumnNames::ROLE_ID_COLUMN => $roleId->toString(),
                ColumnNames::USER_ID_COLUMN => $otherUserId,
            ]
        );

        $this->getConnection()->insert(
            $this->userRoleTableName,
            [
                ColumnNames::ROLE_ID_COLUMN => $roleId->toString(),
                ColumnNames::USER_ID_COLUMN => $userId,
            ]
        );

        $this->repository->removeUserRole($userId, $roleId);

        $rows = $this->getTableRows($this->userRoleTableName);


        $expectedRows = [
            [
                ColumnNames::USER_ID_COLUMN => $otherUserId,
                ColumnNames::ROLE_ID_COLUMN => $roleId->toString(),
            ],
        ];

        $this->assertEquals($expectedRows, $rows);
    }

    private function getTableRows(string $tableName): array
    {
        $sql = 'SELECT * FROM ' . $tableName;

        $statement = $this->getConnection()->executeQuery($sql);
        return $statement->fetchAllAssociative();
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\ReadModel\Permissions\Doctrine;

use CultuurNet\UDB3\DBALTestConnectionTrait;
use CultuurNet\UDB3\Role\ReadModel\Permissions\UserPermissionsReadRepositoryInterface;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\TestCase;

class UserPermissionsReadRepositoryTest extends TestCase
{
    use DBALTestConnectionTrait;

    private UserPermissionsReadRepositoryInterface $repository;

    private string $userRoleTableName;

    private string $rolePermissionTableName;

    public function setUp(): void
    {
        $this->setUpDatabase();

        $this->userRoleTableName = 'user_roles';
        $this->rolePermissionTableName = 'role_permissions';

        $this->repository = new UserPermissionsReadRepository(
            $this->getConnection(),
            $this->userRoleTableName,
            $this->rolePermissionTableName
        );
    }

    /**
     * @test
     */
    public function it_should_return_the_permissions_for_a_user_that_are_granted_by_his_roles(): void
    {
        $userId = '7D23021B-C9AA-4B64-97A5-ECA8168F4A27';
        $roleId = '7B6A161E-987B-4069-8BB2-9956B01782CB';
        $otherRoleId = '8B6A161E-987B-8069-8BB2-9856B01782CB';

        // Add a role for the user
        $this->getConnection()->insert(
            $this->userRoleTableName,
            [
                ColumnNames::ROLE_ID_COLUMN => $roleId,
                ColumnNames::USER_ID_COLUMN => $userId,
            ]
        );

        // Add another role for the user
        $this->getConnection()->insert(
            $this->userRoleTableName,
            [
                ColumnNames::ROLE_ID_COLUMN => $otherRoleId,
                ColumnNames::USER_ID_COLUMN => $userId,
            ]
        );

        // Add some permissions to the role we just assigned to the user
        $this->getConnection()->insert(
            $this->rolePermissionTableName,
            [
                ColumnNames::ROLE_ID_COLUMN => $roleId,
                ColumnNames::PERMISSION_COLUMN => Permission::labelsBeheren()->toString(),
            ]
        );
        $this->getConnection()->insert(
            $this->rolePermissionTableName,
            [
                ColumnNames::ROLE_ID_COLUMN => $roleId,
                ColumnNames::PERMISSION_COLUMN => Permission::gebruikersBeheren()->toString(),
            ]
        );

        // Add a permission to the other role
        $this->getConnection()->insert(
            $this->rolePermissionTableName,
            [
                ColumnNames::ROLE_ID_COLUMN => $otherRoleId,
                ColumnNames::PERMISSION_COLUMN => Permission::gebruikersBeheren()->toString(),
            ]
        );
        $this->getConnection()->insert(
            $this->rolePermissionTableName,
            [
                ColumnNames::ROLE_ID_COLUMN => $otherRoleId,
                ColumnNames::PERMISSION_COLUMN => Permission::aanbodModereren()->toString(),
            ]
        );

        $permissions = $this->repository->getPermissions($userId);

        $expectedPermissions = [
            Permission::labelsBeheren(),
            Permission::gebruikersBeheren(),
            Permission::aanbodModereren(),
        ];
        $this->assertEqualsCanonicalizing($expectedPermissions, $permissions, 'User permissions do not match expected!');

        $otherUserPermissions = $this->repository->getPermissions('otherUserId');
        $this->assertEmpty($otherUserPermissions);
    }

    /**
     * @test
     */
    public function it_can_check_if_a_user_has_a_specific_permission_in_its_roles(): void
    {
        $userId = '7D23021B-C9AA-4B64-97A5-ECA8168F4A27';
        $otherUserId = '7e51485a-adab-443f-b9c5-3cf735572f7c';
        $roleId = '7B6A161E-987B-4069-8BB2-9956B01782CB';
        $otherRoleId = '8B6A161E-987B-8069-8BB2-9856B01782CB';

        // Add a role for the user
        $this->getConnection()->insert(
            $this->userRoleTableName,
            [
                ColumnNames::ROLE_ID_COLUMN => $roleId,
                ColumnNames::USER_ID_COLUMN => $userId,
            ]
        );

        // Add another role for the user
        $this->getConnection()->insert(
            $this->userRoleTableName,
            [
                ColumnNames::ROLE_ID_COLUMN => $otherRoleId,
                ColumnNames::USER_ID_COLUMN => $userId,
            ]
        );

        // Add some permissions to the role we just assigned to the user
        $this->getConnection()->insert(
            $this->rolePermissionTableName,
            [
                ColumnNames::ROLE_ID_COLUMN => $roleId,
                ColumnNames::PERMISSION_COLUMN => Permission::labelsBeheren()->toString(),
            ]
        );
        $this->getConnection()->insert(
            $this->rolePermissionTableName,
            [
                ColumnNames::ROLE_ID_COLUMN => $roleId,
                ColumnNames::PERMISSION_COLUMN => Permission::gebruikersBeheren()->toString(),
            ]
        );

        // Add a permission to the other role
        $this->getConnection()->insert(
            $this->rolePermissionTableName,
            [
                ColumnNames::ROLE_ID_COLUMN => $otherRoleId,
                ColumnNames::PERMISSION_COLUMN => Permission::gebruikersBeheren()->toString(),
            ]
        );
        $this->getConnection()->insert(
            $this->rolePermissionTableName,
            [
                ColumnNames::ROLE_ID_COLUMN => $otherRoleId,
                ColumnNames::PERMISSION_COLUMN => Permission::aanbodModereren()->toString(),
            ]
        );

        $this->assertTrue($this->repository->hasPermission($userId, Permission::gebruikersBeheren()));
        $this->assertTrue($this->repository->hasPermission($userId, Permission::aanbodModereren()));
        $this->assertTrue($this->repository->hasPermission($userId, Permission::labelsBeheren()));

        $this->assertFalse($this->repository->hasPermission($userId, Permission::aanbodBewerken()));
        $this->assertFalse($this->repository->hasPermission($userId, Permission::aanbodVerwijderen()));
        $this->assertFalse($this->repository->hasPermission($userId, Permission::organisatiesBewerken()));
        $this->assertFalse($this->repository->hasPermission($userId, Permission::organisatiesBeheren()));

        $this->assertFalse($this->repository->hasPermission($otherUserId, Permission::gebruikersBeheren()));
    }
}

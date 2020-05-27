<?php

namespace CultuurNet\UDB3\Role\ReadModel\Constraints\Doctrine;

use CultuurNet\UDB3\DBALTestConnectionTrait;
use CultuurNet\UDB3\Role\ReadModel\Constraints\UserConstraintsReadRepositoryInterface;
use CultuurNet\UDB3\Role\ReadModel\Permissions\Doctrine\SchemaConfigurator as PermissionSchemaConfigurator;
use CultuurNet\UDB3\Role\ReadModel\Search\Doctrine\SchemaConfigurator as SearchSchemaConfigurator;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

class UserConstraintsReadRepositoryTest extends TestCase
{
    use DBALTestConnectionTrait;

    /**
     * @var UUID[]
     */
    private $roleIds;

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
     * @var UserConstraintsReadRepositoryInterface
     */
    private $userConstraintsReadRepository;

    protected function setUp()
    {
        $this->roleIds = [new UUID(), new UUID(), new UUID(), new UUID()];

        $this->userRolesTableName = new StringLiteral('user_roles');
        $this->rolePermissionsTableName = new StringLiteral('role_permissions');
        $this->rolesSearchTableName = new StringLiteral('roles_search');

        $permissionSchemaConfigurator = new PermissionSchemaConfigurator(
            $this->userRolesTableName,
            $this->rolePermissionsTableName
        );
        $permissionSchemaConfigurator->configure(
            $this->getConnection()->getSchemaManager()
        );

        $constraintSchemaConfigurator = new SearchSchemaConfigurator(
            $this->rolesSearchTableName
        );
        $constraintSchemaConfigurator->configure(
            $this->getConnection()->getSchemaManager()
        );

        $this->userConstraintsReadRepository = new UserConstraintsReadRepository(
            $this->getConnection(),
            $this->userRolesTableName,
            $this->rolePermissionsTableName,
            $this->rolesSearchTableName
        );

        $this->seedUserRoles();
        $this->seedRolePermissions();
        $this->seedRolesSearch();
    }

    /**
     * @test
     */
    public function it_returns_constraints_for_a_certain_user_and_permission()
    {
        $constraints = $this->userConstraintsReadRepository->getByUserAndPermission(
            new StringLiteral('user1'),
            Permission::AANBOD_MODEREREN()
        );

        $expectedConstraints = [
            new StringLiteral('zipCode:1000'),
            new StringLiteral('zipCode:3000'),
        ];

        $this->assertEquals(
            $expectedConstraints,
            $constraints,
            'Constraints do not match expected!',
            0.0,
            0,
            true
        );
    }

    /**
     * @test
     */
    public function it_returns_empty_array_for_a_missing_user()
    {
        $constraints = $this->userConstraintsReadRepository->getByUserAndPermission(
            new StringLiteral('user3'),
            Permission::AANBOD_MODEREREN()
        );

        $this->assertEmpty($constraints);
    }

    /**
     * @test
     */
    public function it_returns_empty_array_for_a_missing_permission()
    {
        $constraints = $this->userConstraintsReadRepository->getByUserAndPermission(
            new StringLiteral('user2'),
            Permission::AANBOD_BEWERKEN()
        );

        $this->assertEmpty($constraints);
    }

    private function seedUserRoles()
    {
        $this->insertUserRole(new StringLiteral('user1'), $this->roleIds[0]);
        $this->insertUserRole(new StringLiteral('user1'), $this->roleIds[1]);
        $this->insertUserRole(new StringLiteral('user1'), $this->roleIds[2]);
        $this->insertUserRole(new StringLiteral('user2'), $this->roleIds[2]);
        $this->insertUserRole(new StringLiteral('user1'), $this->roleIds[3]);
    }

    private function seedRolePermissions()
    {
        $this->insertUserPermission($this->roleIds[0], Permission::AANBOD_BEWERKEN());
        $this->insertUserPermission($this->roleIds[0], Permission::AANBOD_VERWIJDEREN());
        $this->insertUserPermission($this->roleIds[0], Permission::AANBOD_MODEREREN());

        $this->insertUserPermission($this->roleIds[1], Permission::LABELS_BEHEREN());
        $this->insertUserPermission($this->roleIds[1], Permission::GEBRUIKERS_BEHEREN());

        $this->insertUserPermission($this->roleIds[2], Permission::AANBOD_MODEREREN());

        $this->insertUserPermission($this->roleIds[3], Permission::AANBOD_VERWIJDEREN());
        $this->insertUserPermission($this->roleIds[3], Permission::AANBOD_MODEREREN());
    }

    private function seedRolesSearch()
    {
        $this->insertRole($this->roleIds[0], new StringLiteral('Brussel Validatoren'), new StringLiteral('zipCode:1000'));
        $this->insertRole($this->roleIds[1], new StringLiteral('Antwerpen Validatoren'), new StringLiteral('zipCode:2000'));
        $this->insertRole($this->roleIds[2], new StringLiteral('Leuven Validatoren'), new StringLiteral('zipCode:3000'));
        $this->insertRole($this->roleIds[3], new StringLiteral('Geen constraint'), null);
    }

    /**
     * @param StringLiteral $userId
     * @param UUID $roleId
     */
    private function insertUserRole(StringLiteral $userId, UUID $roleId)
    {
        $this->getConnection()->insert(
            $this->userRolesTableName,
            [
                PermissionSchemaConfigurator::USER_ID_COLUMN => $userId->toNative(),
                PermissionSchemaConfigurator::ROLE_ID_COLUMN => $roleId->toNative(),
            ]
        );
    }

    /**
     * @param UUID $roleId
     * @param Permission $permission
     */
    private function insertUserPermission(UUID $roleId, Permission $permission)
    {
        $this->getConnection()->insert(
            $this->rolePermissionsTableName,
            [
                PermissionSchemaConfigurator::ROLE_ID_COLUMN => $roleId->toNative(),
                PermissionSchemaConfigurator::PERMISSION_COLUMN => $permission->toNative(),
            ]
        );
    }

    /**
     * @param UUID $roleId
     * @param StringLiteral $roleName
     * @param StringLiteral $constraint
     */
    private function insertRole(
        UUID $roleId,
        StringLiteral $roleName,
        StringLiteral $constraint = null
    ) {
        $this->getConnection()->insert(
            $this->rolesSearchTableName,
            [
                SearchSchemaConfigurator::UUID_COLUMN => $roleId->toNative(),
                SearchSchemaConfigurator::NAME_COLUMN => $roleName->toNative(),
                SearchSchemaConfigurator::CONSTRAINT_COLUMN => $constraint ? $constraint->toNative() : null,
            ]
        );
    }
}

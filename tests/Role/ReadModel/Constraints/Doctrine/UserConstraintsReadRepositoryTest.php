<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\ReadModel\Constraints\Doctrine;

use CultuurNet\UDB3\DBALTestConnectionTrait;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Role\ReadModel\Constraints\UserConstraintsReadRepositoryInterface;
use CultuurNet\UDB3\Role\ReadModel\Permissions\Doctrine\SchemaConfigurator as PermissionSchemaConfigurator;
use CultuurNet\UDB3\Role\ReadModel\Search\Doctrine\SchemaConfigurator as SearchSchemaConfigurator;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\TestCase;
use CultuurNet\UDB3\StringLiteral;

class UserConstraintsReadRepositoryTest extends TestCase
{
    use DBALTestConnectionTrait;

    /**
     * @var UUID[]
     */
    private array $roleIds;

    private StringLiteral $userRolesTableName;

    private StringLiteral $rolePermissionsTableName;

    private StringLiteral $rolesSearchTableName;

    private UserConstraintsReadRepositoryInterface $userConstraintsReadRepository;

    protected function setUp(): void
    {
        $this->roleIds = [
            new UUID('36c96c3b-9ce4-492b-9b4e-fee465beb597'),
            new UUID('f874cea2-4f8e-475c-8e97-47f881fc5e1a'),
            new UUID('eec38cda-9e24-441e-9584-2dafe80590a3'),
            new UUID('09e79125-5982-4a0f-aba6-a28774b84699'),
        ];

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
    public function it_returns_constraints_for_a_certain_user_and_permission(): void
    {
        $constraints = $this->userConstraintsReadRepository->getByUserAndPermission(
            new StringLiteral('user1'),
            Permission::aanbodModereren()
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
    public function it_returns_empty_array_for_a_missing_user(): void
    {
        $constraints = $this->userConstraintsReadRepository->getByUserAndPermission(
            new StringLiteral('user3'),
            Permission::aanbodModereren()
        );

        $this->assertEmpty($constraints);
    }

    /**
     * @test
     */
    public function it_returns_empty_array_for_a_missing_permission(): void
    {
        $constraints = $this->userConstraintsReadRepository->getByUserAndPermission(
            new StringLiteral('user2'),
            Permission::aanbodBewerken()
        );

        $this->assertEmpty($constraints);
    }

    private function seedUserRoles(): void
    {
        $this->insertUserRole(new StringLiteral('user1'), $this->roleIds[0]);
        $this->insertUserRole(new StringLiteral('user1'), $this->roleIds[1]);
        $this->insertUserRole(new StringLiteral('user1'), $this->roleIds[2]);
        $this->insertUserRole(new StringLiteral('user2'), $this->roleIds[2]);
        $this->insertUserRole(new StringLiteral('user1'), $this->roleIds[3]);
    }

    private function seedRolePermissions(): void
    {
        $this->insertUserPermission($this->roleIds[0], Permission::aanbodBewerken());
        $this->insertUserPermission($this->roleIds[0], Permission::aanbodVerwijderen());
        $this->insertUserPermission($this->roleIds[0], Permission::aanbodModereren());

        $this->insertUserPermission($this->roleIds[1], Permission::labelsBeheren());
        $this->insertUserPermission($this->roleIds[1], Permission::gebruikersBeheren());

        $this->insertUserPermission($this->roleIds[2], Permission::aanbodModereren());

        $this->insertUserPermission($this->roleIds[3], Permission::aanbodVerwijderen());
        $this->insertUserPermission($this->roleIds[3], Permission::aanbodModereren());
    }

    private function seedRolesSearch(): void
    {
        $this->insertRole($this->roleIds[0], new StringLiteral('Brussel Validatoren'), new StringLiteral('zipCode:1000'));
        $this->insertRole($this->roleIds[1], new StringLiteral('Antwerpen Validatoren'), new StringLiteral('zipCode:2000'));
        $this->insertRole($this->roleIds[2], new StringLiteral('Leuven Validatoren'), new StringLiteral('zipCode:3000'));
        $this->insertRole($this->roleIds[3], new StringLiteral('Geen constraint'), null);
    }


    private function insertUserRole(StringLiteral $userId, UUID $roleId): void
    {
        $this->getConnection()->insert(
            $this->userRolesTableName,
            [
                PermissionSchemaConfigurator::USER_ID_COLUMN => $userId->toNative(),
                PermissionSchemaConfigurator::ROLE_ID_COLUMN => $roleId->toString(),
            ]
        );
    }


    private function insertUserPermission(UUID $roleId, Permission $permission): void
    {
        $this->getConnection()->insert(
            $this->rolePermissionsTableName,
            [
                PermissionSchemaConfigurator::ROLE_ID_COLUMN => $roleId->toString(),
                PermissionSchemaConfigurator::PERMISSION_COLUMN => $permission->toString(),
            ]
        );
    }

    private function insertRole(
        UUID $roleId,
        StringLiteral $roleName,
        StringLiteral $constraint = null
    ): void {
        $this->getConnection()->insert(
            $this->rolesSearchTableName,
            [
                SearchSchemaConfigurator::UUID_COLUMN => $roleId->toString(),
                SearchSchemaConfigurator::NAME_COLUMN => $roleName->toNative(),
                SearchSchemaConfigurator::CONSTRAINT_COLUMN => $constraint ? $constraint->toNative() : null,
            ]
        );
    }
}

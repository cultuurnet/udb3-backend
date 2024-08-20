<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\ReadModel\Constraints\Doctrine;

use CultuurNet\UDB3\DBALTestConnectionTrait;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Role\ReadModel\Constraints\UserConstraintsReadRepositoryInterface;
use CultuurNet\UDB3\Role\ReadModel\Permissions\Doctrine\ColumnNames as PermissionSchemaConfigurator;
use CultuurNet\UDB3\Role\ReadModel\Search\Doctrine\SchemaConfigurator as SearchSchemaConfigurator;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\TestCase;

class UserConstraintsReadRepositoryTest extends TestCase
{
    use DBALTestConnectionTrait;

    /**
     * @var UUID[]
     */
    private array $roleIds;

    private string $userRolesTableName;

    private string $rolePermissionsTableName;

    private string $rolesSearchTableName;

    private UserConstraintsReadRepositoryInterface $userConstraintsReadRepository;

    protected function setUp(): void
    {
        $this->setUpDatabase();

        $this->roleIds = [
            new UUID('36c96c3b-9ce4-492b-9b4e-fee465beb597'),
            new UUID('f874cea2-4f8e-475c-8e97-47f881fc5e1a'),
            new UUID('eec38cda-9e24-441e-9584-2dafe80590a3'),
            new UUID('09e79125-5982-4a0f-aba6-a28774b84699'),
        ];

        $this->userRolesTableName = 'user_roles';
        $this->rolePermissionsTableName = 'role_permissions';
        $this->rolesSearchTableName = 'roles_search_v3';

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
            'user1',
            Permission::aanbodModereren()
        );

        $expectedConstraints = [
            'zipCode:1000',
            'zipCode:3000',
        ];

        $this->assertEqualsCanonicalizing($expectedConstraints, $constraints, 'Constraints do not match expected!');
    }

    /**
     * @test
     */
    public function it_returns_empty_array_for_a_missing_user(): void
    {
        $constraints = $this->userConstraintsReadRepository->getByUserAndPermission(
            'user3',
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
            'user2',
            Permission::aanbodBewerken()
        );

        $this->assertEmpty($constraints);
    }

    private function seedUserRoles(): void
    {
        $this->insertUserRole('user1', $this->roleIds[0]);
        $this->insertUserRole('user1', $this->roleIds[1]);
        $this->insertUserRole('user1', $this->roleIds[2]);
        $this->insertUserRole('user2', $this->roleIds[2]);
        $this->insertUserRole('user1', $this->roleIds[3]);
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
        $this->insertRole($this->roleIds[0], 'Brussel Validatoren', 'zipCode:1000');
        $this->insertRole($this->roleIds[1], 'Antwerpen Validatoren', 'zipCode:2000');
        $this->insertRole($this->roleIds[2], 'Leuven Validatoren', 'zipCode:3000');
        $this->insertRole($this->roleIds[3], 'Geen constraint', null);
    }


    private function insertUserRole(string $userId, UUID $roleId): void
    {
        $this->getConnection()->insert(
            $this->userRolesTableName,
            [
                PermissionSchemaConfigurator::USER_ID_COLUMN => $userId,
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
        string $roleName,
        ?string $constraint
    ): void {
        $this->getConnection()->insert(
            $this->rolesSearchTableName,
            [
                SearchSchemaConfigurator::UUID_COLUMN => $roleId->toString(),
                SearchSchemaConfigurator::NAME_COLUMN => $roleName,
                SearchSchemaConfigurator::CONSTRAINT_COLUMN => $constraint,
            ]
        );
    }
}

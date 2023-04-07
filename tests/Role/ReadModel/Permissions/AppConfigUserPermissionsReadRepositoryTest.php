<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\ReadModel\Permissions;

use CultuurNet\UDB3\Role\ReadModel\Permissions\Doctrine\UserPermissionsReadRepository;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\TestCase;

final class AppConfigUserPermissionsReadRepositoryTest extends TestCase
{

    private AppConfigUserPermissionsReadRepository $repository;

    protected function setUp(): void
    {
        $userPermissionsReadRepository = $this->createMock(UserPermissionsReadRepository::class);
        $config = [
            'jkfhsjkfsdhjk@clients' => [Permission::aanbodBewerken(), Permission::productiesAanmaken()]
        ];

        $this->repository = new AppConfigUserPermissionsReadRepository($userPermissionsReadRepository, $config);
    }

    /**
     * @test
     */
    public function it_returns_all_enabled_permissions_for_client_id_when_executing_get_permissions() {
        $permissions = $this->repository->getPermissions('jkfhsjkfsdhjk@clients');
        $expected = [Permission::aanbodBewerken(), Permission::productiesAanmaken()];

        $this->assertEquals($expected, $permissions);
    }

    /**
     * @test
     */
    public function it_returns_no_permissions_for_unknown_client_id_when_executing_get_permissions() {
        $permissions = $this->repository->getPermissions('nobody@clients');
        $expected = [];

        $this->assertEquals($expected, $permissions);
    }

    /**
     * @test
     */
    public function it_returns_true_for_client_id_with_permission_when_executing_has_permission() {
        $hasPermission = $this->repository->hasPermission('jkfhsjkfsdhjk@clients', Permission::aanbodBewerken());

        $this->assertTrue($hasPermission);
    }

    /**
     * @test
     */
    public function it_returns_false_for_client_id_without_permission_when_executing_has_permission() {
        $hasPermission = $this->repository->hasPermission('jkfhsjkfsdhjk@clients', Permission::filmsAanmaken());

        $this->assertFalse($hasPermission);
    }

    /**
     * @test
     */
    public function it_returns_false_for_unknown_client_id_when_executing_has_permission() {
        $hasPermission = $this->repository->hasPermission('nobody@clients', Permission::aanbodBewerken());

        $this->assertFalse($hasPermission);
    }

}

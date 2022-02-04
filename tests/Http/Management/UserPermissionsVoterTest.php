<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Management;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Role\ReadModel\Permissions\UserPermissionsReadRepositoryInterface;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class UserPermissionsVoterTest extends TestCase
{
    use TokenMockingTrait;

    /**
     * @var UserPermissionsReadRepositoryInterface|MockObject
     */
    protected $permissionRepository;

    protected VoterInterface $voter;

    public function setUp(): void
    {
        $this->permissionRepository = $this->createMock(
            UserPermissionsReadRepositoryInterface::class
        );

        $this->voter = new UserPermissionsVoter($this->permissionRepository);
    }

    /**
     * @test
     */
    public function it_should_grant_access_to_a_user_with_all_the_required_permissions(): void
    {
        $userId = 'df8e224b-d8cf-4911-9157-bd439ee85e5f';
        $userToken = $this->createMockToken($userId);
        $request = $this->createMock(Request::class);
        $grantedPermissions = [
            Permission::gebruikersBeheren(),
            Permission::labelsBeheren(),
        ];

        $this->permissionRepository
            ->expects($this->once())
            ->method('getPermissions')
            ->willReturn($grantedPermissions);

        $access = $this->voter->vote($userToken, $request, $grantedPermissions);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $access);
    }

    /**
     * @test
     */
    public function it_grants_access_to_a_user_with_all_the_required_permissions_and_one_not_supported(): void
    {
        $userId = '1dad2d3a-eb85-4cc2-9f2f-86ca4c8e5dff';
        $userToken = $this->createMockToken($userId);
        $request = $this->createMock(Request::class);
        $grantedPermissions = [
            Permission::gebruikersBeheren(),
            Permission::labelsBeheren(),
            Permission::filmsAanmaken(),
        ];

        $this->permissionRepository
            ->expects($this->once())
            ->method('getPermissions')
            ->willReturn($grantedPermissions);

        $requiredPermissions = [
            Permission::gebruikersBeheren(),
            Permission::labelsBeheren(),
        ];
        $access = $this->voter->vote($userToken, $request, $requiredPermissions);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $access);
    }

    /**
     * @test
     */
    public function it_denies_access_to_a_user_with_missing_required_permissions(): void
    {
        $userId = '03575347-4eb2-4c3f-b57b-ac7bfee905cc';
        $userToken = $this->createMockToken($userId);
        $request = $this->createMock(Request::class);
        $grantedPermissions = [
            Permission::gebruikersBeheren(),
            Permission::labelsBeheren(),
        ];

        $this->permissionRepository
            ->expects($this->once())
            ->method('getPermissions')
            ->willReturn($grantedPermissions);

        $requiredPermissions = $grantedPermissions;
        $requiredPermissions[] = Permission::aanbodModereren();
        $access = $this->voter->vote($userToken, $request, $requiredPermissions);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $access);
    }

    /**
     * @test
     */
    public function it_should_deny_access_to_a_user_without_all_the_required_permissions(): void
    {
        $userId = 'cfc2ebb8-b1d0-48ec-86b0-546e5a9f7c4f';
        $userToken = $this->createMockToken($userId);
        $request = $this->createMock(Request::class);
        $grantedPermissions = [
            Permission::gebruikersBeheren(),
            Permission::labelsBeheren(),
        ];

        $this->permissionRepository
            ->expects($this->once())
            ->method('getPermissions')
            ->willReturn($grantedPermissions);

        $expectedPermissions = $grantedPermissions;
        $expectedPermissions[] = Permission::filmsAanmaken();

        $access = $this->voter->vote($userToken, $request, $expectedPermissions);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $access);
    }
}

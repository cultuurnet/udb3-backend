<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Management;

use CultuurNet\UDB3\Role\ReadModel\Permissions\UserPermissionsReadRepositoryInterface;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use ValueObjects\Identity\UUID;

class UserPermissionsVoterTest extends TestCase
{
    use TokenMockingTrait;

    /**
     * @var UserPermissionsReadRepositoryInterface|MockObject
     */
    protected $permissionRepository;

    /**
     * @var VoterInterface
     */
    protected $voter;

    public function setUp()
    {
        $this->permissionRepository = $this->createMock(
            UserPermissionsReadRepositoryInterface::class
        );

        $this->voter = new UserPermissionsVoter($this->permissionRepository);
    }

    /**
     * @test
     */
    public function it_should_grant_access_to_a_user_with_all_the_required_permissions()
    {
        $userId = UUID::generateAsString();
        $userToken = $this->createMockToken($userId);
        $request = $this->createMock(Request::class);
        $grantedPermissions = [
            Permission::get(Permission::GEBRUIKERS_BEHEREN),
            Permission::get(Permission::LABELS_BEHEREN),
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
    public function it_grants_access_to_a_user_with_all_the_required_permissions_and_one_not_supported()
    {
        $userId = UUID::generateAsString();
        $userToken = $this->createMockToken($userId);
        $request = $this->createMock(Request::class);
        $grantedPermissions = [
            Permission::get(Permission::GEBRUIKERS_BEHEREN),
            Permission::get(Permission::LABELS_BEHEREN),
        ];

        $this->permissionRepository
            ->expects($this->once())
            ->method('getPermissions')
            ->willReturn($grantedPermissions);

        $requiredPermissions = $grantedPermissions;
        $requiredPermissions[] = 'Something not supported';
        $access = $this->voter->vote($userToken, $request, $requiredPermissions);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $access);
    }

    /**
     * @test
     */
    public function it_denies_access_to_a_user_with_missing_required_permissions()
    {
        $userId = UUID::generateAsString();
        $userToken = $this->createMockToken($userId);
        $request = $this->createMock(Request::class);
        $grantedPermissions = [
            Permission::get(Permission::GEBRUIKERS_BEHEREN),
            Permission::get(Permission::LABELS_BEHEREN),
        ];

        $this->permissionRepository
            ->expects($this->once())
            ->method('getPermissions')
            ->willReturn($grantedPermissions);

        $requiredPermissions = $grantedPermissions;
        $requiredPermissions[] = Permission::AANBOD_MODEREREN();
        $access = $this->voter->vote($userToken, $request, $requiredPermissions);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $access);
    }

    /**
     * @test
     */
    public function it_should_deny_access_to_a_user_without_all_the_required_permissions()
    {
        $userId = UUID::generateAsString();
        $userToken = $this->createMockToken($userId);
        $request = $this->createMock(Request::class);
        $grantedPermissions = [
            Permission::get(Permission::GEBRUIKERS_BEHEREN),
            Permission::get(Permission::LABELS_BEHEREN),
        ];

        $this->permissionRepository
            ->expects($this->once())
            ->method('getPermissions')
            ->willReturn($grantedPermissions);

        $access = $this->voter->vote($userToken, $request, Permission::getConstants());

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $access);
    }
}

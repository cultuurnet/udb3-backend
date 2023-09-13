<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security\Permission;

use CultuurNet\UDB3\Role\ReadModel\Permissions\UserPermissionsReadRepositoryInterface;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\TestCase;

class UserPermissionVoterTest extends TestCase
{
    private UserPermissionVoter $userPermissionVoter;

    private string $userId;

    protected function setUp(): void
    {
        $userPermissionsReadRepository = $this->createMock(
            UserPermissionsReadRepositoryInterface::class
        );

        $this->userId = '7fdc57a4-1bdc-40d3-8441-a7d83528a15c';
        $userPermissionsReadRepository->expects($this->once())
            ->method('hasPermission')
            ->willReturnCallback(
                function (string $userId, Permission $permission) {
                    $permissions = [
                        Permission::aanbodBewerken()->toString(),
                        Permission::voorzieningenBewerken()->toString(),
                    ];
                    return $userId === $this->userId &&
                        in_array($permission->toString(), $permissions, true);
                }
            );

        $this->userPermissionVoter = new UserPermissionVoter($userPermissionsReadRepository);
    }

    /**
     * @test
     */
    public function it_returns_true_when_user_has_a_role_with_required_permission(): void
    {
        $this->assertTrue(
            $this->userPermissionVoter->isAllowed(
                Permission::voorzieningenBewerken(),
                '',
                $this->userId
            )
        );
    }

    /**
     * @test
     */
    public function it_returns_false_when_user_does_not_have_a_role_with_required_permission(): void
    {
        $this->assertFalse(
            $this->userPermissionVoter->isAllowed(
                Permission::gebruikersBeheren(),
                '',
                $this->userId
            )
        );
    }

    /**
     * @test
     */
    public function it_returns_false_for_unknown_user(): void
    {
        $this->assertFalse(
            $this->userPermissionVoter->isAllowed(
                Permission::voorzieningenBewerken(),
                '',
                ''
            )
        );
    }
}

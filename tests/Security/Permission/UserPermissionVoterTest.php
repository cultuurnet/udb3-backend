<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security\Permission;

use CultuurNet\UDB3\Role\ReadModel\Permissions\UserPermissionsReadRepositoryInterface;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use CultuurNet\UDB3\StringLiteral;

class UserPermissionVoterTest extends TestCase
{
    /**
     * @var UserPermissionsReadRepositoryInterface|MockObject
     */
    private $userPermissionsReadRepository;

    private Permission $requiredPermission;

    private UserPermissionVoter $userPermissionVoter;

    private StringLiteral $userId;

    protected function setUp(): void
    {
        $this->userPermissionsReadRepository = $this->createMock(
            UserPermissionsReadRepositoryInterface::class
        );

        $this->userId = new StringLiteral('7fdc57a4-1bdc-40d3-8441-a7d83528a15c');
        $this->userPermissionsReadRepository->expects($this->once())
            ->method('hasPermission')
            ->willReturnCallback(
                function (string $userId, Permission $permission) {
                    $permissions = [
                        Permission::aanbodBewerken()->toString(),
                        Permission::voorzieningenBewerken()->toString(),
                    ];
                    return $userId === $this->userId->toNative() &&
                        in_array($permission->toString(), $permissions, true);
                }
            );

        $this->requiredPermission = Permission::voorzieningenBewerken();

        $this->userPermissionVoter = new UserPermissionVoter($this->userPermissionsReadRepository);
    }

    /**
     * @test
     */
    public function it_returns_true_when_user_has_a_role_with_required_permission(): void
    {
        $this->assertTrue(
            $this->userPermissionVoter->isAllowed(
                Permission::voorzieningenBewerken(),
                new StringLiteral(''),
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
                new StringLiteral(''),
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
                new StringLiteral(''),
                new StringLiteral('')
            )
        );
    }
}

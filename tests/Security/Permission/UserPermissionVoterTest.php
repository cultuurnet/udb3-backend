<?php

namespace CultuurNet\UDB3\Security\Permission;

use CultuurNet\UDB3\Role\ReadModel\Permissions\UserPermissionsReadRepositoryInterface;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

class UserPermissionVoterTest extends TestCase
{
    /**
     * @var UserPermissionsReadRepositoryInterface
     */
    private $userPermissionsReadRepository;

    /**
     * @var Permission
     */
    private $requiredPermission;

    /**
     * @var UserPermissionVoter
     */
    private $userPermissionVoter;

    /**
     * @var StringLiteral
     */
    private $userId;

    protected function setUp()
    {
        $this->userPermissionsReadRepository = $this->createMock(
            UserPermissionsReadRepositoryInterface::class
        );

        $this->userId = new StringLiteral('7fdc57a4-1bdc-40d3-8441-a7d83528a15c');
        $this->userPermissionsReadRepository->expects($this->once())
            ->method('getPermissions')
            ->will(
                $this->returnCallback(function (StringLiteral $userId) {
                    if ($userId === $this->userId) {
                        return [
                            Permission::AANBOD_BEWERKEN(),
                            Permission::VOORZIENINGEN_BEWERKEN(),
                        ];
                    } else {
                        return [];
                    }
                })
            );

        $this->requiredPermission = Permission::VOORZIENINGEN_BEWERKEN();

        $this->userPermissionVoter = new UserPermissionVoter(
            $this->userPermissionsReadRepository,
            $this->requiredPermission
        );
    }

    /**
     * @test
     */
    public function it_returns_true_when_user_has_a_role_with_required_permission()
    {
        $this->assertTrue(
            $this->userPermissionVoter->isAllowed(
                Permission::VOORZIENINGEN_BEWERKEN(),
                new StringLiteral(''),
                $this->userId
            )
        );
    }

    /**
     * @test
     */
    public function it_returns_false_when_user_does_not_have_a_role_with_required_permission()
    {
        $this->assertFalse(
            $this->userPermissionVoter->isAllowed(
                Permission::GEBRUIKERS_BEHEREN(),
                new StringLiteral(''),
                $this->userId
            )
        );
    }

    /**
     * @test
     */
    public function it_returns_false_for_unknown_user()
    {
        $this->assertFalse(
            $this->userPermissionVoter->isAllowed(
                Permission::VOORZIENINGEN_BEWERKEN(),
                new StringLiteral(''),
                new StringLiteral('')
            )
        );
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security;

use CultuurNet\UDB3\Offer\ReadModel\Permission\PermissionQueryInterface;
use CultuurNet\UDB3\Offer\Security\Permission\CompositeVoter;
use CultuurNet\UDB3\Security\Permission\GodUserVoter;
use CultuurNet\UDB3\Offer\Security\Permission\OwnerVoter;
use CultuurNet\UDB3\Offer\Security\Permission\RoleConstraintVoter;
use CultuurNet\UDB3\Offer\Security\UserPermissionMatcherInterface;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PermissionVoterSecurityTest extends TestCase
{
    /**
     * @var string
     */
    private $godUserId;

    /**
     * @var string
     */
    private $ownerUserId;

    /**
     * @var string
     */
    private $roleUserId;

    /**
     * @var string
     */
    private $notAllowedUserId;

    /**
     * @var PermissionQueryInterface|MockObject
     */
    private $permissionRepository;

    /**
     * @var UserPermissionMatcherInterface|MockObject
     */
    private $userPermissionMatcher;

    /**
     * @var CompositeVoter
     */
    private $permissionVoter;

    protected function setUp()
    {
        $this->godUserId = 'bb0bf2b3-49ba-4f2a-a1e4-ce7ec93a5ea0';
        $this->ownerUserId = '9cb28282-30a1-4afc-aa23-fc825c7d8ac3';
        $this->roleUserId = 'a8ae681a-3945-4fce-9ec1-aee09e8d0234';
        $this->notAllowedUserId = '4b7d9a94-e4ff-4840-92b2-2f3f37ee99d4';

        $this->permissionRepository = $this->createMock(
            PermissionQueryInterface::class
        );

        $this->userPermissionMatcher = $this->createMock(
            UserPermissionMatcherInterface::class
        );

        $this->permissionVoter = new CompositeVoter(
            new GodUserVoter([$this->godUserId]),
            new OwnerVoter($this->permissionRepository),
            new RoleConstraintVoter($this->userPermissionMatcher)
        );
    }

    private function createSecurityForUserId(?string $userId): PermissionVoterSecurity
    {
        return new PermissionVoterSecurity($userId, $this->permissionVoter);
    }

    /**
     * @test
     */
    public function it_handles_authorizable_command()
    {
        $security = $this->createSecurityForUserId($this->godUserId);

        /** @var AuthorizableCommandInterface|MockObject $authorizableCommand */
        $authorizableCommand = $this->createMock(AuthorizableCommandInterface::class);

        $authorizableCommand->method('getItemId')
            ->willReturn('offerId');

        $authorizableCommand->method('getPermission')
            ->willReturn(Permission::AANBOD_BEWERKEN());

        $allowsUpdate = $security->isAuthorized($authorizableCommand);

        $this->assertTrue($allowsUpdate);
    }
}

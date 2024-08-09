<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security;

use CultuurNet\UDB3\Security\ResourceOwner\ResourceOwnerQuery;
use CultuurNet\UDB3\Security\Permission\AnyOfVoter;
use CultuurNet\UDB3\Security\Permission\GodUserVoter;
use CultuurNet\UDB3\Security\Permission\ResourceOwnerVoter;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PermissionVoterCommandBusSecurityTest extends TestCase
{
    private string $godUserId;

    /**
     * @var ResourceOwnerQuery&MockObject
     */
    private $permissionRepository;

    private AnyOfVoter $permissionVoter;

    protected function setUp(): void
    {
        $this->godUserId = 'bb0bf2b3-49ba-4f2a-a1e4-ce7ec93a5ea0';

        $this->permissionRepository = $this->createMock(
            ResourceOwnerQuery::class
        );

        $this->permissionVoter = new AnyOfVoter(
            new GodUserVoter([$this->godUserId]),
            new ResourceOwnerVoter($this->permissionRepository, false)
        );
    }

    private function createSecurityForUserId(?string $userId): PermissionVoterCommandBusSecurity
    {
        return new PermissionVoterCommandBusSecurity($userId, $this->permissionVoter);
    }

    /**
     * @test
     */
    public function it_handles_authorizable_command(): void
    {
        $security = $this->createSecurityForUserId($this->godUserId);

        /** @var AuthorizableCommand&MockObject $authorizableCommand */
        $authorizableCommand = $this->createMock(AuthorizableCommand::class);

        $authorizableCommand->method('getItemId')
            ->willReturn('offerId');

        $authorizableCommand->method('getPermission')
            ->willReturn(Permission::aanbodBewerken());

        $allowsUpdate = $security->isAuthorized($authorizableCommand);

        $this->assertTrue($allowsUpdate);
    }
}

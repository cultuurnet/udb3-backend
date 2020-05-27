<?php

namespace CultuurNet\UDB3\CommandHandling;

use Broadway\CommandHandling\CommandBusInterface;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Offer\Commands\AuthorizableCommandInterface;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\CommandAuthorizationException;
use CultuurNet\UDB3\Security\SecurityInterface;
use CultuurNet\UDB3\Security\UserIdentificationInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

class AuthorizedCommandBusTest extends TestCase
{
    /**
     * @var CommandBusInterface|ContextAwareInterface|MockObject
     */
    private $decoratee;

    /**
     * @var UserIdentificationInterface|MockObject
     */
    private $userIdentification;

    /**
     * @var SecurityInterface|MockObject
     */
    private $security;

    /**
     * @var AuthorizableCommandInterface|MockObject
     */
    private $command;

    /**
     * @var AuthorizedCommandBus
     */
    private $authorizedCommandBus;

    protected function setUp()
    {
        $this->decoratee = $this->createMock([CommandBusInterface::class, ContextAwareInterface::class]);

        $this->userIdentification = $this->createMock(UserIdentificationInterface::class);

        $this->security = $this->createMock(SecurityInterface::class);

        $this->command = $this->createMock(AuthorizableCommandInterface::class);

        $this->authorizedCommandBus = new AuthorizedCommandBus(
            $this->decoratee,
            $this->userIdentification,
            $this->security
        );
    }

    /**
     * @test
     */
    public function it_delegates_is_authorized_call_to_security()
    {
        /** @var AuthorizableCommandInterface $command */
        $command = $this->createMock(AuthorizableCommandInterface::class);

        $this->mockIsAuthorized(true);

        $this->security->expects($this->once())
            ->method('isAuthorized')
            ->with($command);

        $authorized = $this->authorizedCommandBus->isAuthorized($command);

        $this->assertTrue($authorized);
    }

    /**
     * @test
     */
    public function it_stores_and_returns_user_identification()
    {
        $userIdentification = $this->authorizedCommandBus->getUserIdentification();

        $this->assertEquals($this->userIdentification, $userIdentification);
    }

    /**
     * @test
     */
    public function is_does_not_call_is_authorized_when_command_is_not_an_instance_of_authorizable_command()
    {
        $command = new DummyCommand();

        $this->security->expects($this->never())
            ->method('isAuthorized')
            ->with($command);

        $this->authorizedCommandBus->dispatch($command);
    }

    /**
     * @test
     */
    public function it_throws_command_authorization_exception_when_not_authorized()
    {
        $this->mockIsAuthorized(false);

        $userId = new StringLiteral('userId');
        $this->mockGetId($userId);

        $this->mockGetPermission(Permission::AANBOD_BEWERKEN());
        $this->mockGetItemId('itemId');

        $this->expectException(CommandAuthorizationException::class);

        $this->authorizedCommandBus->dispatch($this->command);
    }

    /**
     * @test
     */
    public function it_calls_parent_dispatch_when_authorized()
    {
        $this->mockIsAuthorized(true);

        $this->decoratee->expects($this->once())
            ->method('dispatch')
            ->with($this->command);

        $this->authorizedCommandBus->dispatch($this->command);
    }

    /**
     * @test
     */
    public function it_should_pass_on_context_to_the_decoratee()
    {
        $context = new Metadata(['user' => 'dirk']);

        $this->decoratee
            ->expects($this->once())
            ->method('setContext')
            ->with($context);

        $this->authorizedCommandBus->setContext($context);
    }

    /**
     * @param bool $isAuthorized
     */
    private function mockIsAuthorized($isAuthorized)
    {
        $this->security
            ->method('isAuthorized')
            ->willReturn($isAuthorized);
    }

    /**
     * @param StringLiteral $userId
     */
    private function mockGetId(StringLiteral $userId)
    {
        $this->userIdentification->method('getId')
            ->willReturn($userId);
    }

    /**
     * @param Permission $permission
     */
    private function mockGetPermission(Permission $permission)
    {
        $this->command->method('getPermission')
            ->willReturn($permission);
    }

    /**
     * @param string $itemId
     */
    private function mockGetItemId($itemId)
    {
        $this->command->method('getItemId')
            ->willReturn($itemId);
    }
}

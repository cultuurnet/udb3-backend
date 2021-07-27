<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\CommandHandling;

use Broadway\CommandHandling\CommandBus;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Security\AuthorizableCommandInterface;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\CommandAuthorizationException;
use CultuurNet\UDB3\Security\Security;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AuthorizedCommandBusTest extends TestCase
{
    /**
     * @var CommandBus|ContextAwareInterface|MockObject
     */
    private $decoratee;

    /**
     * @var string
     */
    private $userId;

    /**
     * @var Security|MockObject
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
        $this->decoratee = $this->createMock([CommandBus::class, ContextAwareInterface::class]);

        $this->userId = '9bd817a3-670e-4720-affa-7636e29073ce';

        $this->security = $this->createMock(Security::class);

        $this->command = $this->createMock(AuthorizableCommandInterface::class);

        $this->authorizedCommandBus = new AuthorizedCommandBus(
            $this->decoratee,
            $this->userId,
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
    public function it_stores_and_returns_user_id()
    {
        $userId = $this->authorizedCommandBus->getUserId();

        $this->assertEquals($this->userId, $userId);
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
        $authorizedCommandBus = new AuthorizedCommandBus(
            $this->decoratee,
            'notAuthorizedUserId',
            $this->security
        );

        $this->mockIsAuthorized(false);

        $this->mockGetPermission(Permission::AANBOD_BEWERKEN());
        $this->mockGetItemId('itemId');

        $this->expectException(CommandAuthorizationException::class);

        $authorizedCommandBus->dispatch($this->command);
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

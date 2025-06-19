<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\CommandHandling;

use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Security\AuthorizableCommand;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\CommandAuthorizationException;
use CultuurNet\UDB3\Security\CommandBusSecurity;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AuthorizedCommandBusTest extends TestCase
{
    private AbstractContextAwareCommandBus&MockObject $decoratee;

    private string $userId;

    private CommandBusSecurity&MockObject $security;

    private AuthorizableCommand&MockObject $command;

    private AuthorizedCommandBus $authorizedCommandBus;

    protected function setUp(): void
    {
        $this->decoratee = $this->createMock(AbstractContextAwareCommandBus::class);

        $this->userId = '9bd817a3-670e-4720-affa-7636e29073ce';

        $this->security = $this->createMock(CommandBusSecurity::class);

        $this->command = $this->createMock(AuthorizableCommand::class);

        $this->authorizedCommandBus = new AuthorizedCommandBus(
            $this->decoratee,
            $this->userId,
            $this->security
        );
    }

    /**
     * @test
     */
    public function it_delegates_is_authorized_call_to_security(): void
    {
        /** @var AuthorizableCommand $command */
        $command = $this->createMock(AuthorizableCommand::class);

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
    public function it_stores_and_returns_user_id(): void
    {
        $userId = $this->authorizedCommandBus->getUserId();

        $this->assertEquals($this->userId, $userId);
    }

    /**
     * @test
     */
    public function is_does_not_call_is_authorized_when_command_is_not_an_instance_of_authorizable_command(): void
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
    public function it_throws_command_authorization_exception_when_not_authorized(): void
    {
        $authorizedCommandBus = new AuthorizedCommandBus(
            $this->decoratee,
            'notAuthorizedUserId',
            $this->security
        );

        $this->mockIsAuthorized(false);

        $this->mockGetPermission(Permission::aanbodBewerken());
        $this->mockGetItemId('itemId');

        $this->expectException(CommandAuthorizationException::class);

        $authorizedCommandBus->dispatch($this->command);
    }

    /**
     * @test
     */
    public function it_calls_parent_dispatch_when_authorized(): void
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
    public function it_should_pass_on_context_to_the_decoratee(): void
    {
        $context = new Metadata(['user' => 'dirk']);

        $this->decoratee
            ->expects($this->once())
            ->method('setContext')
            ->with($context);

        $this->authorizedCommandBus->setContext($context);
    }

    private function mockIsAuthorized(bool $isAuthorized): void
    {
        $this->security
            ->method('isAuthorized')
            ->willReturn($isAuthorized);
    }

    private function mockGetPermission(Permission $permission): void
    {
        $this->command->method('getPermission')
            ->willReturn($permission);
    }

    private function mockGetItemId(string $itemId): void
    {
        $this->command->method('getItemId')
            ->willReturn($itemId);
    }
}

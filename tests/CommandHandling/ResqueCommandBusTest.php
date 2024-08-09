<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\CommandHandling;

use Broadway\CommandHandling\CommandBus;
use Broadway\EventDispatcher\EventDispatcher;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Security\AuthorizableCommand;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\CommandAuthorizationException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ResqueCommandBusTest extends TestCase
{
    /**
     * @var CommandBus&ContextAwareInterface&MockObject
     */
    protected $decoratedCommandBus;

    protected ResqueCommandBus $commandBus;

    /**
     * @var EventDispatcher&MockObject
     */
    protected $dispatcher;

    public function setUp(): void
    {
        $queueName = 'test';

        $this->decoratedCommandBus = $this->createMock(TestContextAwareCommandBus::class);
        $this->dispatcher = $this->createMock(EventDispatcher::class);

        $this->commandBus = new ResqueCommandBus(
            $this->decoratedCommandBus,
            $queueName,
            $this->dispatcher,
            '127.0.0.1',
            6379
        );
    }

    /**
     * @test
     */
    public function it_passes_its_context_to_the_decorated_context_aware_command_bus(): void
    {
        $context = new Metadata(
            [
                'user_id' => 1,
            ]
        );

        $this->decoratedCommandBus->expects($this->once())
            ->method('setContext')
            ->with($context);

        $this->commandBus->setContext($context);
    }

    /**
     * @test
     */
    public function it_throws_command_authorization_exception_when_decoratee_is_an_instance_of_authorized_command_bus_and_command_is_an_instance_of_authorizable_command(): void
    {
        $decoratee = $this->createMock(AuthorizedCommandBusInterface::class);
        $decoratee->method('isAuthorized')
            ->willReturn(false);
        $decoratee->method('getUserId')
            ->willReturn('userId');

        $queueName = 'test';

        $commandBus = new ResqueCommandBus(
            $decoratee,
            $queueName,
            $this->dispatcher,
            '127.0.0.1',
            6379
        );

        $command = $this->createMock(AuthorizableCommand::class);
        $command->method('getPermission')
            ->willReturn(Permission::aanbodBewerken());
        $command->method('getItemId')
            ->willReturn('itemId');

        $decoratee->expects($this->once())
            ->method('isAuthorized');

        $this->expectException(CommandAuthorizationException::class);

        $commandBus->dispatch($command);
    }

    /**
     * @test
     */
    public function on_deferred_dispatch_it_dispatches_the_command_to_the_decorated_command_bus(): void
    {
        $command = new \stdClass();
        $command->foo = 'bar';

        $this->decoratedCommandBus->expects($this->once())
            ->method('dispatch')
            ->with($command);

        $this->commandBus->deferredDispatch($command);
    }

    /**
     * @test
     */
    public function it_emits_context_changes_to_the_event_dispatcher(): void
    {
        $context = new Metadata(
            [
                'user_id' => 1,
            ]
        );

        $this->dispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [
                    ResqueCommandBus::EVENT_COMMAND_CONTEXT_SET,
                    ['context' => $context],
                ],
                [
                    ResqueCommandBus::EVENT_COMMAND_CONTEXT_SET,
                    ['context' => null],
                ]
            );

        $this->commandBus->setContext($context);
        $this->commandBus->setContext();
    }
}

abstract class TestContextAwareCommandBus implements CommandBus, ContextAwareInterface
{
}

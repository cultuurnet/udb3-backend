<?php

namespace CultuurNet\UDB3\CommandHandling;

use Broadway\CommandHandling\CommandBusInterface;
use Broadway\EventDispatcher\EventDispatcherInterface;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Offer\Commands\AuthorizableCommandInterface;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\CommandAuthorizationException;
use CultuurNet\UDB3\Security\UserIdentificationInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

class ResqueCommandBusTest extends TestCase
{

    /**
     * @var CommandBusInterface|ContextAwareInterface|MockObject
     */
    protected $decoratedCommandBus;

    /**
     * @var ResqueCommandBus
     */
    protected $commandBus;

    /**
     * @var EventDispatcherInterface|MockObject
     */
    protected $dispatcher;

    public function setUp()
    {
        $queueName = 'test';

        $this->decoratedCommandBus = $this->createMock(TestContextAwareCommandBus::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->commandBus = new ResqueCommandBus(
            $this->decoratedCommandBus,
            $queueName,
            $this->dispatcher
        );
    }

    /**
     * @test
     */
    public function it_passes_its_context_to_the_decorated_context_aware_command_bus()
    {
        $context = new Metadata(
            [
                'user_id' => 1,
                'user_nick' => 'admin',
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
    public function it_throws_command_authorization_exception_when_decoratee_is_an_instance_of_authorized_command_bus_and_command_is_an_instance_of_authorizable_command()
    {
        $userIdentification = $this->createMock(UserIdentificationInterface::class);
        $userIdentification->method('getId')
            ->willReturn(new StringLiteral('userId'));

        $decoratee = $this->createMock(AuthorizedCommandBusInterface::class);
        $decoratee->method('isAuthorized')
            ->willReturn(false);
        $decoratee->method('getUserIdentification')
            ->willReturn($userIdentification);

        $queueName = 'test';

        $commandBus = new ResqueCommandBus(
            $decoratee,
            $queueName,
            $this->dispatcher
        );

        $command = $this->createMock(AuthorizableCommandInterface::class);
        $command->method('getPermission')
            ->willReturn(Permission::AANBOD_BEWERKEN());
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
    public function on_deferred_dispatch_it_dispatches_the_command_to_the_decorated_command_bus()
    {
        $command = new \stdClass();
        $command->foo = 'bar';

        $this->decoratedCommandBus->expects($this->once())
            ->method('dispatch')
            ->with($command);

        $this->commandBus->deferredDispatch(1, $command);
    }

    /**
     * @test
     */
    public function after_deferred_dispatch_it_resets_the_context_of_the_decorated_context_aware_command_bus()
    {
        $command = new \stdClass();
        $command->target = 'foo';

        $context = new Metadata(
            [
                'user_id' => 1,
                'user_nick' => 'admin',
            ]
        );

        $this->commandBus->setContext($context);

        $this->decoratedCommandBus->expects($this->once())
            ->method('dispatch')
            ->with($command)
            ->id('dispatched');

        $this->decoratedCommandBus->expects($this->once())
            ->method('setContext')
            ->with(null)
            ->after('dispatched');

        $this->commandBus->deferredDispatch(1, $command);
    }

    /**
     * @test
     */
    public function after_deferred_dispatch_even_after_exceptions_it_resets_the_context_of_the_decorated_context_aware_command_bus()
    {
        $exception = new \Exception(
            'Something went wrong in the decorated command bus'
        );

        $command = new \stdClass();
        $command->target = 'foo';

        $context = new Metadata(
            [
                'user_id' => 1,
                'user_nick' => 'admin',
            ]
        );

        $this->commandBus->setContext($context);

        $this->decoratedCommandBus->expects($this->once())
            ->method('dispatch')
            ->with($command)
            ->willThrowException(
                $exception
            )
            ->id('dispatched');

        $this->decoratedCommandBus->expects($this->once())
            ->method('setContext')
            ->with(null)
            ->after('dispatched');

        $this->expectException(
            get_class($exception),
            $exception->getMessage()
        );

        $this->commandBus->deferredDispatch(1, $command);
    }

    /**
     * @test
     */
    public function it_emits_context_changes_to_the_event_dispatcher()
    {
        $context = new Metadata(
            [
                'user_id' => 1,
                'user_nick' => 'admin',
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

        $command = new \stdClass();
        $command->foo = 'bar';

        $this->commandBus->deferredDispatch(1, $command);
    }
}

abstract class TestContextAwareCommandBus implements CommandBusInterface, ContextAwareInterface
{

}

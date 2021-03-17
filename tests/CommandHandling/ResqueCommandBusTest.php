<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\CommandHandling;

use Broadway\CommandHandling\CommandBus;
use Broadway\EventDispatcher\EventDispatcher;
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
     * @var CommandBus|ContextAwareInterface|MockObject
     */
    protected $decoratedCommandBus;

    /**
     * @var ResqueCommandBus
     */
    protected $commandBus;

    /**
     * @var EventDispatcher|MockObject
     */
    protected $dispatcher;

    public function setUp()
    {
        $queueName = 'test';

        $this->decoratedCommandBus = $this->createMock(TestContextAwareCommandBus::class);
        $this->dispatcher = $this->createMock(EventDispatcher::class);

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

        $this->commandBus->deferredDispatch($command);
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
        $this->commandBus->setContext(null);
    }
}

abstract class TestContextAwareCommandBus implements CommandBus, ContextAwareInterface
{
}

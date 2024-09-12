<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Broadway\CommandHandling\Validation;

use Broadway\CommandHandling\CommandBus;
use Broadway\CommandHandling\CommandHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ValidatingCommandBusDecoratorTest extends TestCase
{
    /**
     * @var CommandBus&MockObject
     */
    private $decoratee;

    /**
     * @var CommandValidatorInterface&MockObject
     */
    private $validator;

    private ValidatingCommandBusDecorator $decorator;

    public function setUp(): void
    {
        $this->decoratee = $this->createMock(CommandBus::class);
        $this->validator = $this->createMock(CommandValidatorInterface::class);

        $this->decorator = new ValidatingCommandBusDecorator(
            $this->decoratee,
            $this->validator
        );
    }

    /**
     * @test
     */
    public function it_should_validate_each_command_before_dispatching_it_to_the_decoratee(): void
    {
        $command1 = (object) ['do' => 'something 1'];
        $command2 = (object) ['do' => 'something 2'];
        $command3 = (object) ['do' => 'something 3'];

        $this->validator->expects($this->exactly(3))
            ->method('validate')
            ->withConsecutive(
                [$command1],
                [$command2],
                [$command3]
            );

        $this->decoratee->expects($this->exactly(3))
            ->method('dispatch')
            ->withConsecutive(
                [$command1],
                [$command2],
                [$command3]
            );

        $this->decorator->dispatch($command1);
        $this->decorator->dispatch($command2);
        $this->decorator->dispatch($command3);
    }

    /**
     * @test
     */
    public function it_should_delegate_subscriptions_to_the_decoratee(): void
    {
        /* @var CommandHandler $handler */
        $handler = $this->createMock(CommandHandler::class);

        $this->decoratee->expects($this->once())
            ->method('subscribe')
            ->with($handler);

        $this->decorator->subscribe($handler);
    }

    /**
     * @test
     */
    public function it_does_not_dispatch_on_validate_exception(): void
    {
        $command = (object) ['do' => 'something'];

        $this->validator->expects($this->once())
            ->method('validate')
            ->with($command)
            ->willThrowException(new \Exception());

        $this->decoratee->expects($this->never())
            ->method('dispatch');

        $this->expectException(\Exception::class);

        $this->decorator->dispatch($command);
    }
}

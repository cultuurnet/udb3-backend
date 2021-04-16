<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Broadway\CommandHandling;

use Broadway\CommandHandling\CommandBus;
use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Silex\CommandHandling\RetryingCommandBus;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RetryingCommandBusTest extends TestCase
{
    /**
     * @var MockObject
     */
    private $decoratee;

    /**
     * @var RetryingCommandBus
     */
    private $commandBus;

    protected function setUp()
    {
        $this->decoratee = $this->createMock(CommandBus::class);
        $this->commandBus = new RetryingCommandBus($this->decoratee);
    }

    /**
     * @test
     */
    public function it_decorates_command_dispatching(): void
    {
        $command = (object) ['do' => 'something'];

        $this->decoratee->expects($this->once())
            ->method('dispatch')
            ->with($command);

        $this->commandBus->dispatch($command);
    }

    /**
     * @test
     */
    public function it_decorates_handler_subscriptions()
    {
        /* @var CommandHandler $handler */
        $handler = $this->createMock(CommandHandler::class);

        $this->decoratee->expects($this->once())
            ->method('subscribe')
            ->with($handler);

        $this->commandBus->subscribe($handler);
    }
}

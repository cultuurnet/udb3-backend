<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Broadway\CommandHandling;

use Broadway\CommandHandling\CommandBus;
use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\EventSourcing\DBAL\DBALEventStoreException;
use CultuurNet\UDB3\CommandHandling\RetriedCommandFailed;
use CultuurNet\UDB3\CommandHandling\RetryingCommandBus;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RetryingCommandBusTest extends TestCase
{
    private MockObject $decoratee;

    private RetryingCommandBus $commandBus;

    protected function setUp(): void
    {
        $this->decoratee = $this->createMock(CommandBus::class);
        $this->commandBus = new RetryingCommandBus($this->decoratee);
    }

    /**
     * @test
     */
    public function it_retries_command_dispatching_on_event_store_exception(): void
    {
        $command = (object) ['do' => 'something'];

        $this->decoratee->expects($this->exactly(RetryingCommandBus::MAX_RETRIES))
            ->method('dispatch')
            ->with($command)
            ->willThrowException(new DBALEventStoreException());

        $this->expectException(RetriedCommandFailed::class);
        $this->commandBus->dispatch($command);
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
    public function it_decorates_handler_subscriptions(): void
    {
        /* @var CommandHandler $handler */
        $handler = $this->createMock(CommandHandler::class);

        $this->decoratee->expects($this->once())
            ->method('subscribe')
            ->with($handler);

        $this->commandBus->subscribe($handler);
    }
}

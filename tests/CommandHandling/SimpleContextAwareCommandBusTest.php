<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\CommandHandling;

use Broadway\CommandHandling\CommandHandler;
use Broadway\Domain\Metadata;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SimpleContextAwareCommandBusTest extends TestCase
{
    /**
     * @var SimpleContextAwareCommandBus
     */
    protected $commandBus;

    /**
     * @var ContextAwareInterface&CommandHandler&MockObject
     */
    protected $commandHandler;

    public function setUp(): void
    {
        $this->commandBus = new SimpleContextAwareCommandBus();

        $this->commandHandler = $this->createMock(TestCommandHandler::class);
        $this->commandBus->subscribe(
            $this->commandHandler
        );
    }

    /**
     * @test
     */
    public function on_dispatch_it_delegates_the_work_to_handlers(): void
    {
        $commandOne = new \stdClass();
        $commandOne->target = 1;

        $commandTwo = new \stdClass();
        $commandTwo->target = 2;

        $this->commandHandler->expects($this->exactly(2))
            ->method('handle')
            ->withConsecutive(
                [$commandOne],
                [$commandTwo]
            );

        $this->commandBus->dispatch($commandOne);
        $this->commandBus->dispatch($commandTwo);
    }

    /**
     * @test
     */
    public function on_dispatch_it_passes_its_context_to_context_aware_command_handlers(): void
    {
        $context = new Metadata(
            [
                'user_id' => 1,
            ]
        );

        $this->commandHandler->expects($this->exactly(3))
            ->method('setContext')
            ->withConsecutive(
                [null],
                [$context],
                [null]
            );

        $this->commandBus->dispatch(new \stdClass());

        $this->commandBus->setContext($context);

        $this->commandBus->dispatch(new \stdClass());

        $this->commandBus->setContext(null);

        $this->commandBus->dispatch(new \stdClass());
    }
}

abstract class TestCommandHandler implements CommandHandler, ContextAwareInterface
{
}

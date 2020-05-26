<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\CommandHandling;

use Broadway\CommandHandling\CommandHandlerInterface;
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
     * @var ContextAwareInterface|CommandHandlerInterface|MockObject
     */
    protected $commandHandler;

    public function setUp()
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
    public function on_dispatch_it_delegates_the_work_to_handlers()
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
    public function on_dispatch_it_passes_its_context_to_context_aware_command_handlers()
    {
        $context = new Metadata(
            array(
                'user_id' => 1,
                'user_nick' => 'admin',
            )
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

abstract class TestCommandHandler implements CommandHandlerInterface, ContextAwareInterface
{

}

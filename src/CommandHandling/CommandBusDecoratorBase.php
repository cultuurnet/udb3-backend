<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\CommandHandling;

use Broadway\CommandHandling\CommandBusInterface;
use Broadway\CommandHandling\CommandHandlerInterface;

abstract class CommandBusDecoratorBase implements CommandBusInterface
{
    /**
     * @var CommandBusInterface
     */
    protected $decoratee;

    public function __construct(CommandBusInterface $decoratee)
    {
        $this->decoratee = $decoratee;
    }

    /**
     * Dispatches the command $command to the proper CommandHandler
     *
     * @param mixed $command
     */
    public function dispatch($command)
    {
        $this->decoratee->dispatch($command);
    }

    /**
     * Subscribes the command handler to this CommandBus
     */
    public function subscribe(CommandHandlerInterface $handler)
    {
        $this->decoratee->subscribe($handler);
    }
}

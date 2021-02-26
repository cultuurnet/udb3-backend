<?php

namespace CultuurNet\UDB3\CommandHandling;

use Broadway\CommandHandling\CommandBus;
use Broadway\CommandHandling\CommandHandler;

abstract class CommandBusDecoratorBase implements CommandBus
{
    /**
     * @var CommandBus
     */
    protected $decoratee;

    public function __construct(CommandBus $decoratee)
    {
        $this->decoratee = $decoratee;
    }

    /**
     * Dispatches the command $command to the proper CommandHandler
     *
     */
    public function dispatch($command)
    {
        $this->decoratee->dispatch($command);
    }

    /**
     * Subscribes the command handler to this CommandBus
     */
    public function subscribe(CommandHandler $handler)
    {
        $this->decoratee->subscribe($handler);
    }
}

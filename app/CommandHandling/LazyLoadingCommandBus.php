<?php

namespace CultuurNet\UDB3\Silex\CommandHandling;

use Broadway\CommandHandling\CommandBus;
use Broadway\CommandHandling\CommandHandler;

class LazyLoadingCommandBus implements CommandBus
{
    /**
     * @var bool
     */
    private $first = true;

    /**
     * @var callable
     */
    private $beforeFirstDispatch;

    /**
     * @var CommandBus
     */
    private $commandBus;

    public function __construct(CommandBus $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    public function beforeFirstDispatch(callable $beforeFirstDispatch): void
    {
        $this->beforeFirstDispatch = $beforeFirstDispatch;
    }

    public function dispatch($command)
    {
        if ($this->first) {
            $this->first = false;
            call_user_func($this->beforeFirstDispatch, $this);
        }

        $this->commandBus->dispatch($command);
    }

    public function subscribe(CommandHandler $handler)
    {
        $this->commandBus->subscribe($handler);
    }
}

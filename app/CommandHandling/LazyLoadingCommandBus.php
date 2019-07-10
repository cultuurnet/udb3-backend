<?php

namespace CultuurNet\UDB3\Silex\CommandHandling;

use Broadway\CommandHandling\CommandBusInterface;
use Broadway\CommandHandling\CommandHandlerInterface;

class LazyLoadingCommandBus implements CommandBusInterface
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
     * @var CommandBusInterface
     */
    private $commandBus;

    public function __construct(CommandBusInterface $commandBus)
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

    public function subscribe(CommandHandlerInterface $handler)
    {
        $this->commandBus->subscribe($handler);
    }
}

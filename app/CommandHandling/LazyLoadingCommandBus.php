<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\CommandHandling;

use Broadway\CommandHandling\CommandBus;
use Broadway\CommandHandling\CommandHandler;

class LazyLoadingCommandBus implements CommandBus
{
    private bool $first = true;

    /**
     * @var callable
     */
    private $beforeFirstDispatch;

    private CommandBus $commandBus;

    public function __construct(CommandBus $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    public function beforeFirstDispatch(callable $beforeFirstDispatch): void
    {
        $this->beforeFirstDispatch = $beforeFirstDispatch;
    }

    public function dispatch($command): void
    {
        if ($this->first) {
            $this->first = false;
            call_user_func($this->beforeFirstDispatch, $this);
        }

        $this->commandBus->dispatch($command);
    }

    public function subscribe(CommandHandler $handler): void
    {
        $this->commandBus->subscribe($handler);
    }
}

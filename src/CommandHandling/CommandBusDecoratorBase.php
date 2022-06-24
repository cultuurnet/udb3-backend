<?php

declare(strict_types=1);

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

    public function dispatch($command): void
    {
        $this->decoratee->dispatch($command);
    }

    public function subscribe(CommandHandler $handler): void
    {
        $this->decoratee->subscribe($handler);
    }
}

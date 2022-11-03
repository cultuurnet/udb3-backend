<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

use Broadway\CommandHandling\CommandBus;
use Broadway\CommandHandling\CommandHandler;
use Broadway\CommandHandling\Testing\TraceableCommandBus;
use Exception;

final class ThrowingTraceableCommandBus implements CommandBus
{
    private TraceableCommandBus $commandBus;

    private ?Exception $exception = null;

    public function __construct()
    {
        $this->commandBus = new TraceableCommandBus();
    }

    public function throwsOnDispatch(Exception $exception): void
    {
        $this->exception = $exception;
    }

    public function dispatch($command): void
    {
        $this->commandBus->dispatch($command);

        if ($this->exception) {
            throw $this->exception;
        }
    }

    public function subscribe(CommandHandler $handler): void
    {
        $this->commandBus->subscribe($handler);
    }

    public function getRecordedCommands(): array
    {
        return $this->commandBus->getRecordedCommands();
    }

    public function record(): bool
    {
        return $this->commandBus->record();
    }
}

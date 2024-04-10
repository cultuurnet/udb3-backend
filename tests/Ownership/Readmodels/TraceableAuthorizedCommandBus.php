<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership\Readmodels;

use Broadway\CommandHandling\CommandHandler;
use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\CommandHandling\AuthorizedCommandBusInterface;
use CultuurNet\UDB3\Security\AuthorizableCommand;

final class TraceableAuthorizedCommandBus implements AuthorizedCommandBusInterface
{
    private TraceableCommandBus $traceableCommandBus;
    private bool $disableAuthorization = false;

    public function __construct(TraceableCommandBus $traceableCommandBus)
    {
        $this->traceableCommandBus = $traceableCommandBus;
    }

    public function isAuthorized(AuthorizableCommand $command): bool
    {
        return $this->disableAuthorization;
    }

    public function getUserId(): string
    {
        return '';
    }

    public function disableAuthorization(): void
    {
        $this->disableAuthorization = true;
    }

    public function enableAuthorization(): void
    {
        $this->disableAuthorization = false;
    }

    public function dispatch($command): void
    {
        $this->traceableCommandBus->dispatch($command);
    }

    public function subscribe(CommandHandler $handler): void
    {
        $this->traceableCommandBus->subscribe($handler);
    }

    public function getRecordedCommands(): array
    {
        return $this->traceableCommandBus->getRecordedCommands();
    }

    public function record(): bool
    {
        return $this->traceableCommandBus->record();
    }
}

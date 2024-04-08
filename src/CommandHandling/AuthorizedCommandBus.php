<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\CommandHandling;

use Broadway\CommandHandling\CommandBus;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Security\AuthorizableCommand;
use CultuurNet\UDB3\Security\CommandAuthorizationException;
use CultuurNet\UDB3\Security\CommandBusSecurity;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

class AuthorizedCommandBus extends CommandBusDecoratorBase implements AuthorizedCommandBusInterface, LoggerAwareInterface, ContextAwareInterface
{
    protected ?Metadata $metadata;

    private string $userId;

    private CommandBusSecurity $security;

    private bool $disableAuthorization = false;

    public function __construct(
        CommandBus $decoratee,
        string $userId,
        CommandBusSecurity $security
    ) {
        parent::__construct($decoratee);

        $this->userId = $userId;
        $this->security = $security;
    }

    public function dispatch($command): void
    {
        if ($command instanceof AuthorizableCommand) {
            $authorized = $this->isAuthorized($command);
        } else {
            $authorized = true;
        }

        if ($authorized) {
            parent::dispatch($command);
        } else {
            throw new CommandAuthorizationException(
                $this->userId,
                $command
            );
        }
    }

    public function isAuthorized(AuthorizableCommand $command): bool
    {
        if ($this->disableAuthorization) {
            return true;
        }

        return $this->security->isAuthorized($command);
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        if ($this->decoratee instanceof LoggerAwareInterface) {
            $this->decoratee->setLogger($logger);
        }
    }

    public function setContext(?Metadata $context = null): void
    {
        $this->metadata = $context;

        if ($this->decoratee instanceof ContextAwareInterface) {
            $this->decoratee->setContext($context);
        }
    }

    public function disableAuthorization(): void
    {
        $this->disableAuthorization = true;
    }

    public function enableAuthorization(): void
    {
        $this->disableAuthorization = false;
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\CommandHandling;

use Broadway\CommandHandling\CommandBus;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Security\AuthorizableCommandInterface;
use CultuurNet\UDB3\Security\CommandAuthorizationException;
use CultuurNet\UDB3\Security\CommandBusSecurity;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use ValueObjects\StringLiteral\StringLiteral;

class AuthorizedCommandBus extends CommandBusDecoratorBase implements AuthorizedCommandBusInterface, LoggerAwareInterface, ContextAwareInterface
{
    /**
     * @var Metadata
     */
    protected $metadata;

    /**
     * @var string
     */
    private $userId;

    /**
     * @var CommandBusSecurity
     */
    private $security;

    public function __construct(
        CommandBus $decoratee,
        string $userId,
        CommandBusSecurity $security
    ) {
        parent::__construct($decoratee);

        $this->userId = $userId;
        $this->security = $security;
    }

    /**
     * @inheritdoc
     */
    public function dispatch($command)
    {
        if ($command instanceof AuthorizableCommandInterface) {
            $authorized = $this->isAuthorized($command);
        } else {
            $authorized = true;
        }

        if ($authorized) {
            parent::dispatch($command);
        } else {
            throw new CommandAuthorizationException(
                new StringLiteral($this->userId),
                $command
            );
        }
    }

    public function isAuthorized(AuthorizableCommandInterface $command): bool
    {
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


    public function setContext(Metadata $context = null)
    {
        $this->metadata = $context;

        if ($this->decoratee instanceof ContextAwareInterface) {
            $this->decoratee->setContext($context);
        }
    }
}

<?php

namespace CultuurNet\UDB3\CommandHandling;

use Broadway\CommandHandling\CommandBusInterface;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Offer\Commands\AuthorizableCommandInterface;
use CultuurNet\UDB3\Security\CommandAuthorizationException;
use CultuurNet\UDB3\Security\SecurityInterface;
use CultuurNet\UDB3\Security\UserIdentificationInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

class AuthorizedCommandBus extends CommandBusDecoratorBase implements AuthorizedCommandBusInterface, LoggerAwareInterface, ContextAwareInterface
{
    /**
     * @var Metadata
     */
    protected $metadata;

    /**
     * @var UserIdentificationInterface
     */
    private $userIdentification;

    /**
     * @var SecurityInterface
     */
    private $security;

    /**
     * AuthorizedCommandBus constructor.
     * @param CommandBusInterface $decoratee
     * @param UserIdentificationInterface $userIdentification
     * @param SecurityInterface $security
     */
    public function __construct(
        CommandBusInterface $decoratee,
        UserIdentificationInterface $userIdentification,
        SecurityInterface $security
    ) {
        parent::__construct($decoratee);

        $this->userIdentification = $userIdentification;
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
                $this->userIdentification->getId(),
                $command
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function isAuthorized(AuthorizableCommandInterface $command)
    {
        return $this->security->isAuthorized($command);
    }

    /**
     * @return UserIdentificationInterface
     */
    public function getUserIdentification()
    {
        return $this->userIdentification;
    }

    /**
     * Sets a logger instance on the object.
     *
     * @param LoggerInterface $logger
     *
     * @return void
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->decoratee->setLogger($logger);
    }

    /**
     * @param Metadata|null $context
     */
    public function setContext(Metadata $context = null)
    {
        $this->metadata = $context;

        if ($this->decoratee instanceof ContextAwareInterface) {
            $this->decoratee->setContext($context);
        }
    }
}

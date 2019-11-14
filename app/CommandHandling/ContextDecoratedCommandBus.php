<?php

namespace CultuurNet\UDB3\Silex\CommandHandling;

use Broadway\CommandHandling\CommandBusInterface;
use CultuurNet\Auth\TokenCredentials;
use CultuurNet\UDB3\CommandHandling\CommandBusDecoratorBase;
use CultuurNet\UDB3\CommandHandling\ContextAwareInterface;
use Silex\Application;
use Symfony\Component\HttpFoundation\RequestStack;

class ContextDecoratedCommandBus extends CommandBusDecoratorBase
{
    /**
     * @var Application
     */
    private $application;

    /**
     * @param CommandBusInterface $decoratee
     * @param Application $application
     */
    public function __construct(
        CommandBusInterface $decoratee,
        Application $application
    ) {
        parent::__construct($decoratee);
        $this->application = $application;
    }

    /**
     * @inheritdoc
     */
    public function dispatch($command)
    {
        if ($this->decoratee instanceof ContextAwareInterface) {
            $context = ContextFactory::createFromGlobals($this->application);
            $this->decoratee->setContext($context);
        }

        return $this->decoratee->dispatch($command);
    }
}

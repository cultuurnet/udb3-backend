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
            $context = ContextFactory::createContext(
                $this->application['current_user'],
                $this->application['jwt'],
                $this->application['api_key'],
                $this->application['api_name'],
                $this->application['culturefeed_token_credentials'],
                $this->application['request_stack']->getMasterRequest()
            );

            $this->decoratee->setContext($context);
        }

        return $this->decoratee->dispatch($command);
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\CommandHandling;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\CommandHandling\CommandBusDecoratorBase;
use CultuurNet\UDB3\CommandHandling\ContextAwareInterface;
use Silex\Application;

class ContextDecoratedCommandBus extends CommandBusDecoratorBase
{
    /**
     * @var Application
     */
    private $application;

    public function __construct(
        CommandBus $decoratee,
        Application $application
    ) {
        parent::__construct($decoratee);
        $this->application = $application;
    }

    public function dispatch($command): void
    {
        if ($this->decoratee instanceof ContextAwareInterface) {
            $context = ContextFactory::createFromGlobals($this->application);
            $this->decoratee->setContext($context);
        }
        $this->decoratee->dispatch($command);
    }
}

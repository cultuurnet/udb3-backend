<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\CommandHandling;

use Broadway\CommandHandling\CommandBus;
use Psr\Container\ContainerInterface;

class ContextDecoratedCommandBus extends CommandBusDecoratorBase
{
    private ContainerInterface $container;

    public function __construct(
        CommandBus $decoratee,
        ContainerInterface $container
    ) {
        parent::__construct($decoratee);
        $this->container = $container;
    }

    public function dispatch($command): void
    {
        if ($this->decoratee instanceof ContextAwareInterface) {
            $context = ContextFactory::createFromGlobals($this->container);
            $this->decoratee->setContext($context);
        }
        $this->decoratee->dispatch($command);
    }
}

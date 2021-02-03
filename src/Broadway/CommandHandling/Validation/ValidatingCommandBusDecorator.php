<?php

namespace CultuurNet\UDB3\Broadway\CommandHandling\Validation;

use Broadway\CommandHandling\CommandBusInterface;
use Broadway\CommandHandling\CommandHandlerInterface;

class ValidatingCommandBusDecorator implements CommandBusInterface
{
    /**
     * @var CommandBusInterface
     */
    private $commandBus;

    /**
     * @var CommandValidatorInterface
     */
    private $commandValidator;

    /**
     * @param CommandBusInterface $commandBus
     * @param CommandValidatorInterface $commandValidator
     */
    public function __construct(
        CommandBusInterface $commandBus,
        CommandValidatorInterface $commandValidator
    ) {
        $this->commandBus = $commandBus;
        $this->commandValidator = $commandValidator;
    }

    /**
     * @inheritdoc
     */
    public function dispatch($command)
    {
        $this->commandValidator->validate($command);

        // Normally a CommandBus shouldn't return anything, but we
        // return whatever the decoratee might return for compatibility
        // reasons as long as we cannot enforce a void return type.
        return $this->commandBus->dispatch($command);
    }

    /**
     * @inheritdoc
     */
    public function subscribe(CommandHandlerInterface $handler)
    {
        $this->commandBus->subscribe($handler);
    }
}

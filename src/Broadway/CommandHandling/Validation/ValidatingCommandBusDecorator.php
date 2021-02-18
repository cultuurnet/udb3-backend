<?php

namespace CultuurNet\UDB3\Broadway\CommandHandling\Validation;

use Broadway\CommandHandling\CommandBus;
use Broadway\CommandHandling\CommandHandler;

class ValidatingCommandBusDecorator implements CommandBus
{
    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var CommandValidatorInterface
     */
    private $commandValidator;

    public function __construct(
        CommandBus $commandBus,
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
    public function subscribe(CommandHandler $handler)
    {
        $this->commandBus->subscribe($handler);
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Broadway\CommandHandling\Validation;

use Broadway\CommandHandling\CommandBus;
use Broadway\CommandHandling\CommandHandler;

class ValidatingCommandBusDecorator implements CommandBus
{
    private CommandBus $commandBus;

    private CommandValidatorInterface $commandValidator;

    public function __construct(
        CommandBus $commandBus,
        CommandValidatorInterface $commandValidator
    ) {
        $this->commandBus = $commandBus;
        $this->commandValidator = $commandValidator;
    }

    public function dispatch($command): void
    {
        $this->commandValidator->validate($command);
        $this->commandBus->dispatch($command);
    }

    public function subscribe(CommandHandler $handler): void
    {
        $this->commandBus->subscribe($handler);
    }
}

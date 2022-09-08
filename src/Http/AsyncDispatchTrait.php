<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\CommandHandling\AsyncCommand;

trait AsyncDispatchTrait
{
    protected function dispatchAsyncCommand(CommandBus $commandBus, AsyncCommand $command): string
    {
        $commandBus->dispatch($command);

        return $command->getAsyncCommandId() ?? '00000000-0000-0000-0000-000000000000';
    }
}

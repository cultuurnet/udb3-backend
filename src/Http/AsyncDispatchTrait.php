<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\CommandHandling\AsyncCommand;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;

trait AsyncDispatchTrait
{
    protected function dispatchAsyncCommand(CommandBus $commandBus, AsyncCommand $command): string
    {
        $commandBus->dispatch($command);

        return $command->getAsyncCommandId() ?? Uuid::NIL;
    }
}

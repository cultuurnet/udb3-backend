<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use Broadway\CommandHandling\CommandBus;
use Symfony\Component\Console\Command\Command as BaseCommand;

abstract class AbstractCommand extends BaseCommand
{
    protected CommandBus $commandBus;

    public function __construct(CommandBus $commandBus)
    {
        parent::__construct();
        $this->commandBus = $commandBus;
    }
}

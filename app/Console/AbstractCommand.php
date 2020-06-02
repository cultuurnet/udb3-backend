<?php

namespace CultuurNet\UDB3\Silex\Console;

use Broadway\CommandHandling\CommandBusInterface;
use Symfony\Component\Console\Command\Command as BaseCommand;

abstract class AbstractCommand extends BaseCommand
{
    /**
     * @var CommandBusInterface
     */
    protected $commandBus;

    public function __construct(CommandBusInterface $commandBus)
    {
        parent::__construct();
        $this->commandBus = $commandBus;
    }
}

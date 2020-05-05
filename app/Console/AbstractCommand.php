<?php

namespace CultuurNet\UDB3\Silex\Console;

use Broadway\CommandHandling\CommandBusInterface;
use Knp\Command\Command;

abstract class AbstractCommand extends Command
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

<?php

namespace CultuurNet\UDB3\Silex\Console;

use Broadway\CommandHandling\CommandBusInterface;
use Knp\Command\Command;
use phpDocumentor\Reflection\DocBlock\Tags\Return_;

abstract class AbstractCommand extends Command
{
    /**
     * @var CommandBusInterface
     */
    private $commandBus;

    public function __construct(CommandBusInterface $commandBus)
    {
        parent::__construct();
        $this->commandBus = $commandBus;
    }

    protected function getCommandBus(): CommandBusInterface
    {
        return $this->commandBus;
    }
}

<?php

namespace CultuurNet\UDB3\Silex\Console;

use Broadway\CommandHandling\CommandBusInterface;
use CultuurNet\UDB3\Event\Commands\Conclude;
use CultuurNet\UDB3\Silex\Impersonator;
use Knp\Command\Command;

abstract class AbstractConcludeCommand extends AbstractSystemUserCommand
{
    /**
     * @param string $cdbid
     */
    protected function dispatchConclude($cdbid)
    {
        $commandBus = $this->getCommandBus();
        $commandBus->dispatch(new Conclude($cdbid));
    }

    /**
     * @return CommandBusInterface
     */
    private function getCommandBus()
    {
        $app = $this->getSilexApplication();

        return $app['event_command_bus'];
    }
}

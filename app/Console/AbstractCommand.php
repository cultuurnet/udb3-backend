<?php

namespace CultuurNet\UDB3\Silex\Console;

use Broadway\CommandHandling\CommandBusInterface;
use Knp\Command\Command;

abstract class AbstractCommand extends Command
{
    /**
     * @return CommandBusInterface
     */
    protected function getCommandBus()
    {
        $app = $this->getSilexApplication();

        return $app['event_command_bus'];
    }
}

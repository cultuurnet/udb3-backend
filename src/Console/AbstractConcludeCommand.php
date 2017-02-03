<?php

namespace CultuurNet\UDB3\Silex\Console;

use Broadway\CommandHandling\CommandBusInterface;
use CultuurNet\UDB3\Event\Commands\Conclude;
use CultuurNet\UDB3\Silex\Impersonator;
use Knp\Command\Command;

abstract class AbstractConcludeCommand extends Command
{
    protected function impersonateUDB3SystemUser()
    {
        $app = $this->getSilexApplication();

        /** @var Impersonator $impersonator */
        $impersonator = $app['impersonator'];

        $impersonator->impersonate($app['udb3_system_user_metadata']);
    }

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

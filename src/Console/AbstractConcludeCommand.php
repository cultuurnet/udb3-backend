<?php

namespace CultuurNet\UDB3\Silex\Console;

use CultuurNet\UDB3\Event\Commands\Conclude;

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
}

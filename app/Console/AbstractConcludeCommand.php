<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Console;

use CultuurNet\UDB3\Event\Commands\Conclude;

abstract class AbstractConcludeCommand extends AbstractCommand
{
    /**
     * @param string $cdbid
     */
    protected function dispatchConclude($cdbid)
    {
        $this->commandBus->dispatch(new Conclude($cdbid));
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Console;

use Broadway\CommandHandling\CommandBus;
use Symfony\Component\Console\Command\Command as BaseCommand;

abstract class AbstractCommand extends BaseCommand
{
    /**
     * @var CommandBus
     */
    protected $commandBus;

    public function __construct(CommandBus $commandBus)
    {
        parent::__construct();
        $this->commandBus = $commandBus;
    }
}

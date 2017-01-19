<?php

namespace CultuurNet\UDB3\Silex\Console;

use Broadway\CommandHandling\CommandBusInterface;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\CommandHandling\ResqueCommandBus;
use CultuurNet\UDB3\CommandHandling\SimpleContextAwareCommandBus;
use CultuurNet\UDB3\Event\Commands\Conclude;
use CultuurNet\UDB3\Silex\Impersonator;
use Knp\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConcludeCommand extends Command
{
    public function configure()
    {
        $this
            ->setName('event:conclude')
            ->setDescription('Conclude a specific event.')
            ->addArgument(
                'cdbid',
                InputArgument::REQUIRED
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $commandBus = $this->getCommandBus();

        $commandBus->dispatch(
            new Conclude($input->getArgument('cdbid'))
        );
    }

    /**
     * @return CommandBusInterface
     */
    private function getCommandBus()
    {
        $app = $this->getSilexApplication();

        /** @var Impersonator $impersonator */
        $impersonator = $app['impersonator'];

        // Before initializing the command bus, impersonate the system user.
        $impersonator->impersonate($app['udb3_system_user_metadata']);

        $commandBus = $app['event_command_bus'];

        return $commandBus;
    }
}

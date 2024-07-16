<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use CultuurNet\UDB3\Event\Productions\ProductionId;
use CultuurNet\UDB3\Event\Productions\RemoveEventsFromProduction;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class BulkRemoveFromProduction extends AbstractCommand
{
    protected function configure(): void
    {
        $this->setName('event:bulk-remove-from-production')
            ->setDescription('Bulk removes events from a production')
            ->addArgument(
                'productionId',
                InputOption::VALUE_REQUIRED,
                'The id of the production contains incorrect events.'
            )
            ->addOption(
                'eventId',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'An array of eventIds to remove from the production.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $command = new RemoveEventsFromProduction(
            $input->getOption('eventId'),
            ProductionId::fromNative($input->getArgument('productionId'))
        );
        $this->commandBus->dispatch($command);

        return 0;
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use CultuurNet\UDB3\Label\Commands\IncludeLabel as IncludeLabelCommand;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class IncludeLabel extends AbstractCommand
{
    public function configure(): void
    {
        $this->setName('label:include');
        $this->setDescription('Includes a label with the given uuid');
        $this->addArgument('labelId', InputArgument::REQUIRED, 'Uuid of the label');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $labelId = $input->getArgument('labelId');

        $this->commandBus->dispatch(
            new IncludeLabelCommand(new UUID($labelId))
        );

        $output->writeln('label with id ' . $labelId . ' successfully included.');
        return 0;
    }
}

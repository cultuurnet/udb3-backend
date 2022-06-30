<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Console;

use CultuurNet\UDB3\Organizer\Commands\RemoveLabel;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RemoveLabelOrganizer extends AbstractRemoveLabel
{
    public function configure(): void
    {
        $this->setName('organizer:remove-label');
        $this->setDescription('Removes a label with the uuid');
        $this->addArgument('organizerId', InputArgument::REQUIRED, 'Uuid of the organizer');
        $this->addArgument('labelId', InputArgument::REQUIRED, 'Uuid of the label');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $organizerId = $input->getArgument('organizerId');
        $labelId = $input->getArgument('labelId');

        $label = $this->getLabel($labelId);
        if (!$label) {
            $output->writeln('Label with Id ' . $labelId . ' does not exist.');
            return 1;
        }

        $this->commandBus->dispatch(
            new RemoveLabel($organizerId, $this->getLabel($labelId))
        );

        return 0;
    }
}

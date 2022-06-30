<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Console;

use CultuurNet\UDB3\Offer\Commands\RemoveLabel;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RemoveLabelOffer extends AbstractRemoveLabel
{
    public function configure(): void
    {
        $this->setName('offer:remove-label');
        $this->setDescription('Removes invalid labels');
        $this->addArgument('offerId', InputArgument::REQUIRED, 'Uuid of the offer');
        $this->addArgument('labelId', InputArgument::REQUIRED, 'Uuid of the label');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $offerId = $input->getArgument('offerId');
        $labelId = $input->getArgument('labelId');

        $label = $this->getLabel($labelId);
        if (!$label) {
            $output->writeln('Label with Id ' . $labelId . ' does not exist.');
            return 1;
        }

        $this->commandBus->dispatch(
            new RemoveLabel($offerId, $this->getLabel($labelId))
        );

        return 0;
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Console;

use CultuurNet\UDB3\Offer\Commands\ChangeOwner;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class ChangeOfferOwner extends AbstractCommand
{
    public function configure(): void
    {
        $this->setName('offer:change-owner');
        $this->setDescription('Change the owner of an offer to a new user id');
        $this->addArgument('offerId', InputArgument::REQUIRED, 'Uuid of the offer');
        $this->addArgument(
            'newOwnerId',
            InputArgument::REQUIRED,
            'Id of the new user. '
            . 'Can either be a v1 id (e.g. "97f0f81d-a2b6-44c5-ab27-e076d6329f91") '
            . 'or a v2 id (e.g. "auth0|2836d202-1955-40f4-aee4-1b2ea493f17c"). '
            . 'Always use v1 ids for users migrated from UiTID v1!'
        );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->setVerbosity(OutputInterface::VERBOSITY_VERY_VERBOSE);
        $logger = new ConsoleLogger($output);

        $offerId = $input->getArgument('offerId');
        $newOwnerId = $input->getArgument('newOwnerId');

        $this->commandBus->dispatch(
            new ChangeOwner(
                $offerId,
                $newOwnerId
            )
        );
        $logger->info('Successfully changed owner of offer "' . $offerId . '" to user with id "' . $newOwnerId . '"');

        return 0;
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Console;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Organizer\Commands\ChangeOwner;
use CultuurNet\UDB3\Security\ResourceOwner\ResourceOwnerQuery;
use CultuurNet\UDB3\StringLiteral;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

final class ChangeOrganizerOwnerInBulk extends AbstractCommand
{
    private ResourceOwnerQuery $permissionQuery;

    public function __construct(
        CommandBus $commandBus,
        ResourceOwnerQuery $permissionQuery
    ) {
        parent::__construct($commandBus);
        $this->permissionQuery = $permissionQuery;
    }

    public function configure(): void
    {
        $this->setName('organizer:change-owner-bulk');
        $this->setDescription('Change the owner of multiple organizers to a new user id');
        $this->addArgument('originalOwnerId', InputArgument::REQUIRED, 'Id of the original owner');
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

        $originalOwnerId = $input->getArgument('originalOwnerId');
        $newOwnerId = $input->getArgument('newOwnerId');

        $success = 0;
        $errors = 0;
        foreach ($this->permissionQuery->getEditableResourceIds(new StringLiteral($originalOwnerId)) as $editableOrganizers) {
            $organizerId = $editableOrganizers->toNative();
            try {
                $this->commandBus->dispatch(
                    new ChangeOwner(
                        $organizerId,
                        $newOwnerId
                    )
                );
                $logger->info(
                    'Successfully changed owner of organizer "' . $organizerId . '" to user with id "' . $newOwnerId . '"'
                );
                $success++;
            } catch (Throwable $t) {
                $logger->error(
                    sprintf(
                        'An error occurred while changing owner of organizer "%s": %s with message %s',
                        $organizerId,
                        get_class($t),
                        $t->getMessage()
                    )
                );
                $errors++;
            }
        }

        $logger->info('Successfully changed owner of ' . $success . ' organizers to user with id "' . $newOwnerId . '"');

        if ($errors) {
            $logger->error('Failed to change owner of ' . $errors . ' organizers');
        }

        return $errors > 0 ? 1 : 0;
    }
}

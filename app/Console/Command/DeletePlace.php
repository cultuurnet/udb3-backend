<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Event\Commands\UpdateLocation;
use CultuurNet\UDB3\Event\ReadModel\Relations\EventRelationsRepository;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Offer\Commands\DeleteOffer;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * @url https://jira.publiq.be/browse/III-6109
 */
class DeletePlace extends AbstractCommand
{
    private const FORCE = 'force';
    private const DRY_RUN = 'dry-run';
    private const PLACE_UUID = 'place-uuid';
    private const CANONICAL_UUID = 'canonical-uuid';

    private EventRelationsRepository $eventRelationsRepository;

    public function __construct(
        CommandBus $commandBus,
        EventRelationsRepository $eventRelationsRepository
    ) {
        parent::__construct($commandBus);
        $this->eventRelationsRepository = $eventRelationsRepository;
    }

    public function configure(): void
    {
        $this
            ->setName('place:delete')
            ->setDescription('Mark one place as deleted, and move all it\'s events to the canonical place')
            ->addArgument(
                self::PLACE_UUID,
                null,
                'Place uuid to delete.'
            )
            ->addArgument(
                self::CANONICAL_UUID,
                null,
                'Canonical place uuid to move all events towards.'
            )
            ->addOption(
                self::FORCE,
                null,
                InputOption::VALUE_NONE,
                'Skip confirmation.'
            )
            ->addOption(
                self::DRY_RUN,
                null,
                InputOption::VALUE_NONE,
                'Execute the script as a dry run.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $placeUuid = $input->getArgument(self::PLACE_UUID);
        $canonicalUuid = $input->getArgument(self::CANONICAL_UUID);

        if ($placeUuid === null || $canonicalUuid === null) {
            $output->writeln('<error>Missing argument, the correct syntax is: place:delete place_uuid_to_delete canonical_place_uuid</error>');
            return 0;
        }

        if (!$this->askConfirmation($input, $output)) {
            return 0;
        }

        foreach ($this->eventRelationsRepository->getEventsLocatedAtPlace($placeUuid) as $eventLocatedAtPlace) {
            $command = new UpdateLocation($eventLocatedAtPlace, new LocationId($canonicalUuid));
            $output->writeln('Dispatching UpdateLocation for event with id ' . $command->getItemId());
            if (!$input->getOption(self::DRY_RUN)) {
                $this->commandBus->dispatch($command);
            }
        }

        $output->writeln('Dispatching DeleteOffer for place with id ' . $placeUuid);

        if (!$input->getOption(self::DRY_RUN)) {
            $this->commandBus->dispatch(new DeleteOffer($placeUuid));
        }

        return 1;
    }

    private function askConfirmation(InputInterface $input, OutputInterface $output): bool
    {
        if ($input->getOption(self::FORCE)) {
            return true;
        }

        return $this
            ->getHelper('question')
            ->ask(
                $input,
                $output,
                new ConfirmationQuestion(
                    'This action will mark this place as deleted, continue? [y/N] ',
                    true
                )
            );
    }
}

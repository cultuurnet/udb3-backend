<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Offer\Commands\UpdateOrganizer;
use CultuurNet\UDB3\Search\ResultsGenerator;
use CultuurNet\UDB3\Search\ResultsGeneratorInterface;
use CultuurNet\UDB3\Search\SearchServiceInterface;
use CultuurNet\UDB3\Search\Sorting;
use Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

final class ChangeOrganizerOnOffer extends AbstractCommand
{
    private const ORGANIZER_UUID = 'organizer-uuid';
    private const QUERY = 'query';
    private const FORCE = 'force';
    private const DRY_RUN = 'dry-run';

    private ResultsGeneratorInterface $searchResultsGenerator;

    public function __construct(
        CommandBus $commandBus,
        SearchServiceInterface $searchService
    ) {
        parent::__construct($commandBus);
        $this->searchResultsGenerator = new ResultsGenerator(
            $searchService,
            new Sorting('created', 'asc'),
            100
        );
    }

    public function configure(): void
    {
        $this
            ->setName('offer:change-organizer')
            ->setDescription('Update the organizer of all offers from the given SAPI3 query to the given new organizer')
            ->addArgument(
                self::ORGANIZER_UUID,
                null,
                'Organizer uuid to move offers to.'
            )
            ->addArgument(
                self::QUERY,
                null,
                'SAPI3 query for which offers to move.'
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
        $organizerId = $input->getArgument(self::ORGANIZER_UUID);
        $query = $input->getArgument(self::QUERY);

        if ($organizerId === null || $query === null) {
            $output->writeln('<error>Missing argument, the correct syntax is: offer:change-organizer "organizer_uuid_to_move_to" "sapi3_query"</error>');
            return self::FAILURE;
        }

        $query = str_replace('q=', '', $query);

        $count = $this->searchResultsGenerator->count($query);

        if ($count <= 0) {
            $output->writeln('<error>No offers found</error>');
            return self::SUCCESS;
        }

        if (!$this->askConfirmation($input, $output, $count)) {
            return self::SUCCESS;
        }

        foreach ($this->searchResultsGenerator->search($query) as $offer) {
            try {
                $command = new UpdateOrganizer($offer->getId(), $organizerId);
                $output->writeln('Dispatching UpdateOrganizer for offer with id ' . $command->getItemId());

                if (!$input->getOption(self::DRY_RUN)) {
                    $this->commandBus->dispatch($command);
                }
            } catch (Exception $exception) {
                $output->writeln(sprintf('<error>Offer with id: %s caused an exception: %s</error>', $offer->getId(), $exception->getMessage()));
            }
        }

        return self::SUCCESS;
    }

    private function askConfirmation(InputInterface $input, OutputInterface $output, int $count): bool
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
                    sprintf('This action will move %d offers, continue? [y/N] ', $count),
                    true
                )
            );
    }
}

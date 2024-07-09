<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Event\Commands\UpdateLocation;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Search\ResultsGenerator;
use CultuurNet\UDB3\Search\ResultsGeneratorInterface;
use CultuurNet\UDB3\Search\SearchServiceInterface;
use CultuurNet\UDB3\Search\Sorting;
use Exception;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * @url https://jira.publiq.be/browse/III-6228
 */
class MoveEvents extends AbstractCommand
{
    private const PLACE_UUID = 'place-uuid';
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
            ->setName('event:move')
            ->setDescription('Move an event from one place to another based on a SAPI3 query')
            ->addArgument(
                self::PLACE_UUID,
                null,
                'Place uuid to move events to.'
            )
            ->addArgument(
                self::QUERY,
                null,
                'SAPI3 query for which events to move.'
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
        $query = $input->getArgument(self::QUERY);

        if ($placeUuid === null || $query === null) {
            $output->writeln('<error>Missing argument, the correct syntax is: event:move "place_uuid_to_move_to" "sapi3_query"</error>');
            return self::FAILURE;
        }

        $count = $this->searchResultsGenerator->count($query);

        if ($count <= 0) {
            $output->writeln('<error>No events found</error>');
            return self::FAILURE;
        }

        if (!$this->askConfirmation($input, $output, $count)) {
            return self::FAILURE;
        }

        $progressBar = new ProgressBar($output, $count);

        $exceptions = [];
        foreach ($this->searchResultsGenerator->search($query) as $event) {
            try {
                $command = new UpdateLocation($event->getId(), new LocationId($placeUuid));
                if (!$input->getOption(self::DRY_RUN)) {
                    $this->commandBus->dispatch($command);
                }
                $output->writeln('Dispatching UpdateLocation for event with id ' . $command->getItemId());
            } catch (Exception $exception) {
                $exceptions[] = sprintf('Event with id: %s caused an exception: %s', $event->getId(), $exception->getMessage());
            }

            $progressBar->advance();
        }

        $progressBar->finish();

        $output->writeln('');
        foreach ($exceptions as $exception) {
            $output->writeln(sprintf('<error>%s</error>', $exception));
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
                    sprintf('This action will move %d events, continue? [y/N] ', $count),
                    true
                )
            );
    }
}

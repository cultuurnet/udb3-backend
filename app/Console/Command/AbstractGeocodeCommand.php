<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Search\Sorting;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\Search\ResultsGenerator;
use CultuurNet\UDB3\Search\ResultsGeneratorInterface;
use CultuurNet\UDB3\Search\SearchServiceInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

abstract class AbstractGeocodeCommand extends AbstractCommand
{
    private const OPT_ALL = 'all';

    private ResultsGeneratorInterface $searchResultsGenerator;

    private DocumentRepository $documentRepository;

    public function __construct(
        CommandBus $commandBus,
        SearchServiceInterface $searchService,
        DocumentRepository $documentRepository
    ) {
        parent::__construct($commandBus);
        $this->searchResultsGenerator = new ResultsGenerator(
            $searchService,
            new Sorting('created', 'asc'),
            100
        );
        $this->documentRepository = $documentRepository;
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'id',
                null,
                InputOption::VALUE_IS_ARRAY|InputOption::VALUE_OPTIONAL,
                'Fixed list of ids of the items to geocode.'
            )
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'Skip confirmation.'
            )
            ->addOption(
                self::OPT_ALL,
                null,
                InputOption::VALUE_NONE,
                'Update all items that are not rejected or deleted.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        if ($input->getOption('id')) {
            return $this->geocodeManually($input, $output);
        }
        return $this->geocodeByQuery($input, $output);
    }

    private function geocodeManually(InputInterface $input, OutputInterface $output): int
    {
        $ids = array_values(array_filter($input->getOption('id')));
        $count = count($ids);

        if ($count === 0) {
            $output->writeln('Please enter at least one id to geocode.');
            return 0;
        }

        if (!$this->askConfirmation($input, $output, $count)) {
            return 0;
        }

        foreach ($ids as $id) {
            $this->dispatchGeocodingCommand($id, $output);
        }

        return 0;
    }

    private function geocodeByQuery(InputInterface $input, OutputInterface $output): int
    {
        $query = $this->getQueryForMissingCoordinates($input->getOption(self::OPT_ALL));
        $count = $this->searchResultsGenerator->count($query);

        if ($count === 0) {
            $output->writeln('Could not find any items with missing or outdated coordinates.');
            return 0;
        }

        if (!$this->askConfirmation($input, $output, $count)) {
            return 0;
        }

        $results = $this->searchResultsGenerator->search($query);
        foreach ($results as $id => $result) {
            $this->dispatchGeocodingCommand($id, $output);
        }

        return 0;
    }

    private function askConfirmation(InputInterface $input, OutputInterface $output, int $count): bool
    {
        if ($input->getOption('force')) {
            return true;
        }

        return $this
            ->getHelper('question')
            ->ask(
                $input,
                $output,
                new ConfirmationQuestion(
                    "This action will queue {$count} items for geocoding, continue? [y/N] ",
                    true
                )
            );
    }

    protected function getDocument(string $id): ?JsonDocument
    {
        try {
            return $this->documentRepository->fetch($id);
        } catch (DocumentDoesNotExist $e) {
            return null;
        }
    }

    abstract protected function dispatchGeocodingCommand(string $itemId, OutputInterface $output): void;

    abstract protected function getQueryForMissingCoordinates(bool $all): string;
}

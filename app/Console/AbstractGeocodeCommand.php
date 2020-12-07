<?php

namespace CultuurNet\UDB3\Silex\Console;

use Broadway\CommandHandling\CommandBusInterface;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\Search\ResultsGenerator;
use CultuurNet\UDB3\Search\ResultsGeneratorInterface;
use CultuurNet\UDB3\Search\SearchServiceInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

abstract class AbstractGeocodeCommand extends AbstractCommand
{
    /**
     * @var ResultsGeneratorInterface
     */
    private $searchResultsGenerator;

    /**
     * @var DocumentRepository
     */
    private $documentRepository;

    public function __construct(
        CommandBusInterface $commandBus,
        SearchServiceInterface $searchService,
        DocumentRepository $documentRepository
    ) {
        parent::__construct($commandBus);
        $this->searchResultsGenerator = new ResultsGenerator(
            $searchService,
            ['created' => 'asc'],
            100
        );
        $this->documentRepository = $documentRepository;
    }

    protected function getDocument(string $id): ?JsonDocument
    {
        try {
            return $this->documentRepository->fetch($id);
        } catch (DocumentDoesNotExist $e) {
            return null;
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        if ($input->getOption('cdbid')) {
            return $this->geocodeManually($input, $output);
        }
        return $this->geocodeByQuery($input, $output);
    }

    private function geocodeManually(InputInterface $input, OutputInterface $output): ?int
    {
        $cdbids = array_values(array_filter($input->getOption('cdbid')));
        $count = count($cdbids);

        if ($count === 0) {
            $output->writeln("Please enter at least one cdbid to geocode.");
            return 0;
        }

        if (!$this->askConfirmation($input, $output, $count)) {
            return 0;
        }

        foreach ($cdbids as $cdbid) {
            $this->dispatchGeocodingCommand($cdbid, $output);
        }

        return 0;
    }

    private function geocodeByQuery(InputInterface $input, OutputInterface $output): ?int
    {
        $query = $this->getQueryForMissingCoordinates();
        $count = $this->searchResultsGenerator->count($query);

        if ($count === 0) {
            $output->writeln("Could not find any items with missing or outdated coordinates.");
            return 0;
        }

        if (!$this->askConfirmation($input, $output, $count)) {
            return 0;
        }

        $results = $this->searchResultsGenerator->search($query);
        foreach ($results as $cdbid => $result) {
            $this->dispatchGeocodingCommand($cdbid, $output);
        }

        return 0;
    }

    private function askConfirmation(InputInterface $input, OutputInterface $output, int $count): bool
    {
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

    abstract protected function dispatchGeocodingCommand(string $itemId, OutputInterface $output): void;

    abstract protected function getQueryForMissingCoordinates(): string;
}

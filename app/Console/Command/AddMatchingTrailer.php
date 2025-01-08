<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Kinepolis\Trailer\TrailerRepository;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Offer\Commands\Video\AddVideo;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\Search\ResultsGenerator;
use CultuurNet\UDB3\Search\ResultsGeneratorInterface;
use CultuurNet\UDB3\Search\SearchServiceInterface;
use CultuurNet\UDB3\Search\Sorting;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class AddMatchingTrailer extends Command
{
    private CommandBus $commandBus;

    private ResultsGeneratorInterface $searchResultsGenerator;

    private DocumentRepository $documentRepository;

    private TrailerRepository $trailerRepository;

    public function __construct(
        CommandBus $commandBus,
        SearchServiceInterface $searchService,
        DocumentRepository $documentRepository,
        TrailerRepository $trailerRepository
    ) {
        parent::__construct();
        $this->commandBus = $commandBus;
        $this->searchResultsGenerator = new ResultsGenerator(
            $searchService,
            new Sorting('created', 'desc'),
            100
        );
        $this->documentRepository = $documentRepository;
        $this->trailerRepository = $trailerRepository;
    }

    public function configure(): void
    {
        $this->setName('movies:add-trailers');
        $this->setDescription('Try to find a matching trailer for a movie');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $query = $this->getQueryForMoviesWithoutTrailer();
        $count = $this->searchResultsGenerator->count($query);

        if ($count === 0) {
            $output->writeln('Could not find any movies without trailer.');
            return 0;
        }

        $results = $this->searchResultsGenerator->search($query);
        foreach ($results as $eventId => $result) {
            $this->dispatchAddVideoCommand($eventId, $output);
        }

        return 0;
    }

    private function getQueryForMoviesWithoutTrailer(): string
    {
        return 'terms.id:0.50.6.0.0 AND videosCount:0 AND creator:' . Uuid::NIL;
    }

    private function dispatchAddVideoCommand(string $eventId, OutputInterface $output): void
    {
        try {
            $document = $this->documentRepository->fetch($eventId);
        } catch (DocumentDoesNotExist $e) {
            $output->writeln("Skipping {$eventId}. (Could not find JSON-LD in local repository.)");
            return;
        }

        $jsonLd = Json::decodeAssociatively($document->getRawBody());
        $mainLanguage = $jsonLd->mainLanguage ?? 'nl';

        if (!isset($jsonLd['name'][$mainLanguage])) {
            $output->writeln("Skipping {$eventId}. (Could not find a name.)");
            return;
        }

        $video = $this->trailerRepository->findMatchingTrailer($jsonLd['name'][$mainLanguage]);

        if ($video === null) {
            $output->writeln("Skipping {$eventId}. (Could not find a trailer.)");
            return;
        }

        $this->commandBus->dispatch(
            new AddVideo($eventId, $video)
        );
        $output->writeln("Added trailer for {$eventId}.");
    }
}

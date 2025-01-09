<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use Broadway\CommandHandling\CommandBus;
use Cake\Chronos\Chronos;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Kinepolis\Trailer\TrailerRepository;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\Video;
use CultuurNet\UDB3\Offer\Commands\Video\AddVideo;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\Search\ResultsGenerator;
use CultuurNet\UDB3\Search\ResultsGeneratorInterface;
use CultuurNet\UDB3\Search\SearchServiceInterface;
use CultuurNet\UDB3\Search\Sorting;
use Google\Service\Exception as GoogleException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class AddMatchingTrailer extends Command
{
    private const ONE_WEEK_LIMIT = 'one-week-limit';

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
        $this->setName('movies:add-trailers')
            ->setDescription('Try to find a matching trailer for a movie')
            ->addOption(
                self::ONE_WEEK_LIMIT,
                '-l',
                InputOption::VALUE_NONE,
                'Limit the search to movies created during the last week.'
            );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $query = $this->getQueryForMoviesWithoutTrailer($input);
        $count = $this->searchResultsGenerator->count($query);

        if ($count === 0) {
            $output->writeln('Could not find any movies without trailer.');
            return 0;
        }

        $results = $this->searchResultsGenerator->search($query);
        foreach ($results as $eventId => $result) {
            try {
                $trailer = $this->searchForTrailer($eventId, $output);
            } catch (GoogleException $exception) {
                $output->writeln($exception->getMessage());
                break;
            }
            if ($trailer !== null) {
                $this->dispatchAddVideoCommand($eventId, $trailer);
                $output->writeln("Added trailer for {$eventId}.");
            }
        }

        return 0;
    }

    private function getQueryForMoviesWithoutTrailer(InputInterface $input): string
    {
        $query = 'terms.id:0.50.6.0.0 AND videosCount:0 AND creator:' . Uuid::NIL;
        if ($input->getOption(self::ONE_WEEK_LIMIT)) {
            $query .= ' AND created:[' . Chronos::now()->subWeeks(1)->toIso8601String() . ' TO *]';
        }
        return $query;
    }

    private function searchForTrailer(string $eventId, OutputInterface $output): ?Video
    {
        try {
            $document = $this->documentRepository->fetch($eventId);
        } catch (DocumentDoesNotExist $e) {
            $output->writeln("Skipping {$eventId}. (Could not find JSON-LD in local repository.)");
            return null;
        }

        $jsonLd = Json::decodeAssociatively($document->getRawBody());
        $mainLanguage = $jsonLd->mainLanguage ?? 'nl';

        if (!isset($jsonLd['name'][$mainLanguage])) {
            $output->writeln("Skipping {$eventId}. (Could not find a name.)");
            return null;
        }

        $video = $this->trailerRepository->findMatchingTrailer($jsonLd['name'][$mainLanguage]);

        if ($video === null) {
            $output->writeln("Skipping {$eventId}. (Could not find a trailer.)");
        }

        return $video;
    }

    private function dispatchAddVideoCommand(string $eventId, Video $trailer): void
    {
        $this->commandBus->dispatch(
            new AddVideo($eventId, $trailer)
        );
    }
}

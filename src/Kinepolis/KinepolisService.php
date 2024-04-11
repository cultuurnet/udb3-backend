<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Kinepolis;

use Broadway\CommandHandling\CommandBus;
use Broadway\Repository\Repository;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\Calendar\Calendar as LegacyCalendar;
use CultuurNet\UDB3\Event\Commands\UpdateDescription;
use CultuurNet\UDB3\Event\Event as EventAggregate;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Language as LegacyLanguage;
use CultuurNet\UDB3\Offer\Commands\UpdateCalendar;
use Psr\Log\LoggerInterface;

final class KinepolisService
{
    private CommandBus $commandBus;

    private Repository $aggregateRepository;

    private KinepolisClient $client;

    private Parser $parser;

    private MappingRepository $movieMappingRepository;

    private UuidGeneratorInterface $uuidGenerator;

    private LoggerInterface $logger;

    public function __construct(
        CommandBus $commandBus,
        Repository $aggregateRepository,
        KinepolisClient $client,
        Parser $parser,
        MappingRepository $movieMappingRepository,
        UuidGeneratorInterface $uuidGenerator,
        LoggerInterface $logger
    ) {
        $this->commandBus = $commandBus;
        $this->aggregateRepository = $aggregateRepository;
        $this->client = $client;
        $this->parser = $parser;
        $this->movieMappingRepository = $movieMappingRepository;
        $this->uuidGenerator = $uuidGenerator;
        $this->logger = $logger;
    }

    public function fetch(): void
    {
        try {
            $token = $this->client->getToken();
        } catch (\Exception $exception) {
            $this->logger->error('Problem with Kinepolis Service: ' . $exception->getMessage());
            return;
        }
        $movies = $this->client->getMovies($token);

        $this->logger->info('Found ' . sizeof($movies) . ' movie productions.');

        foreach ($movies as $movie) {
            $mid = $movie['mid'];
            $movieDetail = $this->client->getMovieDetail($token, $mid);
            $parsedMovies = $this->parser->getParsedMovies($movieDetail);

            $this->logger->info('Found ' . sizeof($parsedMovies) . ' screenings for movie with kinepolisId ' . $mid);

            foreach ($parsedMovies as $parsedMovie) {
                $this->dispatch($parsedMovie);
            }
        }
    }

    private function dispatch(ParsedMovie $parsedMovie): void
    {
        $commands = [];
        $eventId = $this->movieMappingRepository->getByMovieId($parsedMovie->getExternalId());

        if ($eventId === null) {
            $eventId = $this->createNewMovie($parsedMovie);
        } else {
            $updateCalendar = new UpdateCalendar($eventId, LegacyCalendar::fromUdb3ModelCalendar($parsedMovie->getCalendar()));
            $commands[] = $updateCalendar;
            $this->logger->info('Event with id ' . $eventId . ' updated');
        }

        $updateDescription = new UpdateDescription(
            $eventId,
            new LegacyLanguage('nl'),
            $parsedMovie->getDescription()
        );
        $commands[] = $updateDescription;

        foreach ($commands as $command) {
            $this->commandBus->dispatch($command);
        }
    }

    private function createNewMovie(ParsedMovie $parsedMovie): string
    {
        $eventId = $this->uuidGenerator->generate();
        $eventAggregate = EventAggregate::create(
            $eventId,
            new LegacyLanguage('nl'),
            $parsedMovie->getTitle(),
            new EventType('0.50.6.0.0', 'Film'),
            $parsedMovie->getLocationId(),
            LegacyCalendar::fromUdb3ModelCalendar($parsedMovie->getCalendar()),
            $parsedMovie->getTheme()
        );

        $this->aggregateRepository->save($eventAggregate);
        $this->movieMappingRepository->create($eventId, $parsedMovie->getExternalId());
        $this->logger->info('Event created with id ' . $eventId);
        return $eventId;
    }
}

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

final class KinepolisService
{
    private CommandBus $commandBus;

    private Repository $aggregateRepository;

    private KinepolisClient $client;

    private Parser $parser;

    private MovieMappingRepository $movieMappingRepository;

    private UuidGeneratorInterface $uuidGenerator;

    public function __construct(
        CommandBus $commandBus,
        Repository $aggregateRepository,
        KinepolisClient $client,
        Parser $parser,
        MovieMappingRepository $movieMappingRepository,
        UuidGeneratorInterface $uuidGenerator
    ) {
        $this->commandBus = $commandBus;
        $this->aggregateRepository = $aggregateRepository;
        $this->client = $client;
        $this->parser = $parser;
        $this->movieMappingRepository = $movieMappingRepository;
        $this->uuidGenerator = $uuidGenerator;
    }

    public function getClient(): KinepolisClient
    {
        return $this->client;
    }

    public function getParser(): KinepolisParser
    {
        return $this->parser;
    }

    public function start(): void
    {
        $token = $this->getClient()->getToken();
        $movies = $this->getClient()->getMovies($token);

        foreach ($movies as $movie) {
            $mid = $movie['mid'];
            $movieDetail = $this->getClient()->getMovieDetail($token, $mid);
            $parsedMovies = $this->getParser()->getParsedMovies($movieDetail);

            foreach ($parsedMovies as $parsedMovie) {
                $this->dispatch($parsedMovie);
            }
        }
    }

    private function dispatch(ParsedMovie $parsedMovie): void
    {
        $commands = [];
        $eventId = $this->movieMappingRepository->getByMovieId($parsedMovie->getExternalId());
        $eventExists = $eventId !== null;
        if (!$eventExists) {
            $eventId = $this->createNewMovie($parsedMovie);
        } else {
            $updateCalendar = new UpdateCalendar($eventId, LegacyCalendar::fromUdb3ModelCalendar($parsedMovie->getCalendar()));
            $commands[] = $updateCalendar;
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
        return $eventId;
    }
}

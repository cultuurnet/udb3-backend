<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use Broadway\CommandHandling\CommandBus;
use Broadway\Repository\Repository;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\Calendar\Calendar as LegacyCalendar;
use CultuurNet\UDB3\Event\Event as EventAggregate;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Kinepolis\KinepolisService;
use CultuurNet\UDB3\Language as LegacyLanguage;
use CultuurNet\UDB3\Kinepolis\ParsedMovie;
use CultuurNet\UDB3\Kinepolis\MovieRepository;
use CultuurNet\UDB3\Offer\Commands\UpdateCalendar;
use CultuurNet\UDB3\Event\Commands\UpdateDescription;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class FetchMovies extends AbstractCommand
{
    private Repository $aggregateRepository;

    private KinepolisService $service;

    private UuidGeneratorInterface $uuidGenerator;

    private MovieRepository $movieRepository;

    public function __construct(
        CommandBus $commandBus,
        Repository $aggregateRepository,
        KinepolisService $service,
        UuidGeneratorInterface $uuidGenerator,
        MovieRepository $movieRepository
    ) {
        parent::__construct($commandBus);
        $this->aggregateRepository = $aggregateRepository;
        $this->service = $service;
        $this->uuidGenerator = $uuidGenerator;
        $this->movieRepository = $movieRepository;
    }
    public function configure(): void
    {
        $this->setName('movies:fetch');
        $this->setDescription('Fetches movies from an external API');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $token = $this->service->getClient()->getToken();
        $movies = $this->service->getClient()->getMovies($token);

        foreach ($movies as $movie) {
            $mid = $movie['mid'];
            $movieDetail = $this->service->getClient()->getMovieDetail($token, $mid);
            $parsedMovies = $this->service->getParser()->getParsedMovies($movieDetail);

            foreach ($parsedMovies as $parsedMovie) {
                $this->dispatch($parsedMovie);
            }
        }

        return 0;
    }

    private function dispatch(ParsedMovie $parsedMovie): void
    {
        $commands = [];
        $eventId = $this->movieRepository->getEventIdByMovieId($parsedMovie->getExternalId());
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
        $this->movieRepository->addRelation($eventId, $parsedMovie->getExternalId());
        return $eventId;
    }
}

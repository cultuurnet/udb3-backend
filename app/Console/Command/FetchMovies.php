<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use Broadway\CommandHandling\CommandBus;
use Broadway\Repository\Repository;
use Broadway\UuidGenerator\UuidGeneratorInterface;
use CultuurNet\UDB3\Calendar\Calendar as LegacyCalendar;
use CultuurNet\UDB3\Description;
use CultuurNet\UDB3\Event\Commands\CreateEvent;
use CultuurNet\UDB3\Event\Commands\Moderation\Publish;
use CultuurNet\UDB3\Event\Event as EventAggregate;
use CultuurNet\UDB3\Event\EventThemeResolver;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Calendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\MultipleSubEventsCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SingleSubEventCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvents;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use CultuurNet\UDB3\Language as LegacyLanguage;
use CultuurNet\UDB3\Movie\MovieParser;
use CultuurNet\UDB3\Movie\MovieProduction;
use CultuurNet\UDB3\Movie\MovieRepository;
use CultuurNet\UDB3\Movie\MovieService;
use CultuurNet\UDB3\Offer\Commands\UpdateCalendar;
use CultuurNet\UDB3\Offer\InvalidWorkflowStatusTransition;
use CultuurNet\UDB3\Place\Commands\UpdateDescription;
use CultuurNet\UDB3\Theme as LegacyTheme;
use DateTimeImmutable;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class FetchMovies extends AbstractCommand
{
    private Repository $aggregateRepository;

    private UuidGeneratorInterface $uuidGenerator;

    private MovieRepository $movieRepository;

    private MovieService $movieService;

    private MovieParser $movieParser;

    public function __construct(
        CommandBus $commandBus,
        Repository $aggregateRepository,
        UuidGeneratorInterface $uuidGenerator,
        MovieRepository $movieRepository,
        MovieService $movieService,
        MovieParser $movieParser
    ) {
        parent::__construct($commandBus);
        $this->aggregateRepository = $aggregateRepository;
        $this->uuidGenerator = $uuidGenerator;
        $this->movieRepository = $movieRepository;
        $this->movieService = $movieService;
        $this->movieParser = $movieParser;
    }
    public function configure(): void
    {
        $this->setName('movies:fetch');
        $this->setDescription('Fetches movies from an external API');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $token = $this->movieService->getToken();
        $movies = $this->movieService->getMovies($token);
        foreach ($movies as $movie) {
            $mid = $movie['mid'];
            $movieDetail = $this->movieService->getMovieDetail($token, $mid);

            $themeId = $this->movieParser->getThemeId($movieDetail['genre']);

            $movieProduction = new MovieProduction(
                $mid,
                new Title($movieDetail['title']),
                new Description($movieDetail['desc']),
                (new EventThemeResolver())->byId($themeId)
            );

            $screenings = $this->movieParser->parse($movieDetail);
            foreach ($screenings as $screeningLocation => $screening) {
                foreach ($screening as $version => $subEvents) {
                    $this->parseMovieScreening($movieProduction, $screeningLocation, $version, $subEvents);
                }
            }
        }
        return 0;
    }

    private function parseMovieScreening(
        MovieProduction $movieProduction,
        string $screeningLocation,
        string $version,
        array $subEvents
    ): void {
        $commands = [];
        $movieId = $this->movieParser->generateMovieId($movieProduction->getMid(), $screeningLocation, $version);
        $eventId = $this->movieRepository->getEventIdByMovieId($movieId);
        $eventExists = $eventId !== null;
        $locationId = new LocationId($this->movieParser->getLocationId($screeningLocation));
        $calendar = sizeof($subEvents) === 1 ?
            new SingleSubEventCalendar(...$subEvents) :
            new MultipleSubEventsCalendar(new SubEvents(...$subEvents));
        if (!$eventExists) {
            $eventId = $this->uuidGenerator->generate();
             $this->createMovie(
                $eventId,
                $movieProduction->getTitle(),
                $locationId,
                $calendar,
                $movieProduction->getTheme()
            );

            $commands[] = new Publish($eventId, new DateTimeImmutable());
            $this->movieRepository->addRelation($eventId, $movieId);
        } else {
            $updateCalendar = new UpdateCalendar($eventId, LegacyCalendar::fromUdb3ModelCalendar($calendar));
            $commands[] = $updateCalendar;
        }

        $updateDescription = new UpdateDescription(
            $eventId,
            new LegacyLanguage('nl'),
            $movieProduction->getDescription()
        );
        $commands[] = $updateDescription;

        foreach ($commands as $command) {
            try {
            $this->commandBus->dispatch($command);
            } catch (InvalidWorkflowStatusTransition $notAllowedToPublish) {
            }
        }
    }

    private function createMovie(string $eventId, Title $title, LocationId $locationId, Calendar $calendar, LegacyTheme $theme): void
    {
        $eventAggregate = EventAggregate::create(
            $eventId,
            new LegacyLanguage('nl'),
            $title,
            new EventType('0.50.6.0.0', 'Film'),
            $locationId,
            LegacyCalendar::fromUdb3ModelCalendar($calendar),
            $theme
        );

        $this->aggregateRepository->save($eventAggregate);
    }
}

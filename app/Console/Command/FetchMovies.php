<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use Broadway\CommandHandling\CommandBus;
use Broadway\UuidGenerator\Rfc4122\Version4Generator;
use CultuurNet\UDB3\Calendar\Calendar as LegacyCalendar;
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
use CultuurNet\UDB3\Movie\MovieRepository;
use CultuurNet\UDB3\Movie\MovieService;
use CultuurNet\UDB3\Theme as LegacyTheme;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class FetchMovies extends AbstractCommand
{
    private MovieRepository $movieRepository;

    private MovieService $movieService;

    private MovieParser $movieParser;

    public function __construct(
        CommandBus $commandBus,
        MovieRepository $movieRepository,
        MovieService $movieService,
        MovieParser $movieParser
    ) {
        parent::__construct($commandBus);
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

            $title = new Title($movieDetail['title']);
            $themeId = $this->movieParser->getThemeId($movieDetail['genre']);
            $theme = (new EventThemeResolver())->byId($themeId);

            $screenings = $this->movieParser->parse($movieDetail);
            foreach ($screenings as $screeningLocation => $screening) {
                $movieId = $this->movieParser->generateMovieId($mid, $screeningLocation, '2D');
                $eventId = $this->movieRepository->getEventIdByMovieId($movieId);
                $locationId = new LocationId($this->movieParser->getLocationId($screeningLocation));
                $calendar = sizeof($screening['2D']) === 1 ?
                    new MultipleSubEventsCalendar(new SubEvents(...$screening['2D'])) :
                    new SingleSubEventCalendar(...$screening['2D']);
                if (is_null($eventId)) {
                    $this->createMovie($title, $locationId, $calendar, $theme);
                }
            }
        }
        $output->writeln('TODO');
        return 0;
    }

    private function createMovie(Title $title, LocationId $locationId, Calendar $calendar, LegacyTheme $theme): void
    {
        EventAggregate::create(
            (new Version4Generator())->generate(),
            new LegacyLanguage('nl'),
            $title,
            new EventType('0.50.6.0.0', 'Film'),
            $locationId,
            LegacyCalendar::fromUdb3ModelCalendar($calendar),
            $theme
        );
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Kinepolis;

use CultuurNet\UDB3\Description;
use CultuurNet\UDB3\Event\EventThemeResolver;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Model\ValueObject\Calendar\MultipleSubEventsCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SingleSubEventCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvents;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;

final class KinepolisParser
{
    private array $termsMapper;

    private array $theatreMapper;

    private DateParser $dateParser;

    public function __construct(
        array $termsMapper,
        array $theatreMapper,
        DateParser $dateParser
    ) {
        $this->termsMapper = $termsMapper;
        $this->theatreMapper = $theatreMapper;
        $this->dateParser = $dateParser;
    }

    /**
     * @return ParsedMovie[]
     */
    public function getParsedMovies(array $moviesToParse): array
    {
        $parsedMovies = [];
        $mid = $moviesToParse['mid'];
        $title = $moviesToParse['title'];
        $description = $moviesToParse['desc'];
        $themeId = $this->getThemeId($moviesToParse['genre']);

        // Some movies do not have a length in the external API,
        // so we put the endTime equal to the startTime as a fallback.
        $length = $moviesToParse['length'] ?? 0;
        $dates = $moviesToParse['dates'];
        $parsedDates = $this->dateParser->processDates($dates, $length);
        foreach ($parsedDates as $theatreId => $versions) {
            foreach ($versions as $version => $subEvents) {
                $title = $version === '3D' ? $title . ' 3D' : $title;
                $calendar = sizeof($subEvents) === 1 ?
                    new SingleSubEventCalendar(...$subEvents) :
                    new MultipleSubEventsCalendar(new SubEvents(...$subEvents));
                $parsedMovies[] = new ParsedMovie(
                    $this->generateMovieId($mid, $theatreId, $version),
                    new Title($title),
                    new LocationId($this->getLocationId($theatreId)),
                    new Description($description),
                    (new EventThemeResolver())->byId($themeId),
                    $calendar
                );
            }
        }
        return $parsedMovies;
    }

    private function getLocationId(string $tid): string
    {
        return $this->theatreMapper[$tid];
    }

    private function getThemeId(array $genreIds): string
    {
        // This is a best effort to match the External categorization to a valid theme in the publiq Taxonomy
        // A Match is not guaranteed
        foreach ($genreIds as $genreId) {
            if (array_key_exists($genreId, $this->termsMapper)) {
                return $this->termsMapper[$genreId];
            }
        }
        return '1.7.14.0.0'; // Use "Meerdere filmgenres" as a fallback
    }

    private function generateMovieId(int $mid, string $tid, string $version): string
    {
        return 'Kinepolis:' . 't' . $tid . 'm' . $mid . $version === '3D' ? 'v3D' : '';
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Kinepolis\Parser;

use CultuurNet\UDB3\Event\EventThemeResolver;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Kinepolis\ParsedMovie;
use CultuurNet\UDB3\Kinepolis\ParsedPriceForATheater;
use CultuurNet\UDB3\Model\ValueObject\Calendar\MultipleSubEventsCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SingleSubEventCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvents;
use CultuurNet\UDB3\Model\ValueObject\Price\PriceInfo;
use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;

final class KinepolisParser implements Parser
{
    public const LONG_MOVIE_MINIMUM_LENGTH = 135;
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
     * @param ParsedPriceForATheater[] $parsedPrices
     * @return ParsedMovie[]
     */
    public function getParsedMovies(array $moviesToParse, array $parsedPrices): array
    {
        $parsedMovies = [];
        $mid = $moviesToParse['mid'];
        $title = $moviesToParse['title'];
        $poster = $moviesToParse['poster'];
        $description = $moviesToParse['desc'];
        $themeId = $this->getThemeId($moviesToParse['genre']);

        // Some movies do not have a length in the external API,
        // so we put the endTime equal to the startTime as a fallback.
        $length = $moviesToParse['length'] ?? 0;
        $dates = $moviesToParse['dates'];

        $parsedDates = $this->dateParser->processDates($dates, $length);

        foreach ($parsedDates as $theatreId => $versions) {
            foreach ($versions as $dimension => $subEvents) {
                // Add 3D to the title if it's a 3D version
                // Needed to show the difference on the Output Channels
                // like UiTinVlaanderen
                $is3D = $dimension === '3D';
                $title = $is3D ? $title . ' 3D' : $title;

                $isLong = $length >= self::LONG_MOVIE_MINIMUM_LENGTH;

                $parsedPrice = $parsedPrices[$theatreId];

                $calendar = count($subEvents) === 1 ?
                    new SingleSubEventCalendar(...$subEvents) :
                    new MultipleSubEventsCalendar(new SubEvents(...$subEvents));
                $parsedMovies[] = new ParsedMovie(
                    $this->generateMovieId($mid, $theatreId, $is3D),
                    new Title($title),
                    new LocationId($this->getLocationId($theatreId)),
                    new Description($description),
                    (new EventThemeResolver())->byId($themeId),
                    $calendar,
                    new PriceInfo(
                        $parsedPrice->getBaseTariff($isLong, $is3D),
                        $parsedPrice->getOtherTariffs($isLong, $is3D)
                    ),
                    $poster
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
        // This is the best effort to match the External categorization to a valid theme in the publiq Taxonomy
        // A Match is not guaranteed
        foreach ($genreIds as $genreId) {
            if (array_key_exists($genreId, $this->termsMapper)) {
                return $this->termsMapper[$genreId];
            }
        }
        return '1.7.14.0.0'; // Use "Meerdere filmgenres" as a fallback
    }

    // The external movieApi has no unique ID for what the publiqApi defines to be an Event
    // The function creates an externalId based on the movieId, locationId & version
    // to create a unique identifier that is useable by UiTdatabank.
    private function generateMovieId(int $mid, string $tid, bool $is3D): string
    {
        $version = $is3D ? 'v3D' : '';
        return 'Kinepolis:' . 't' . $tid . 'm' . $mid . $version;
    }
}

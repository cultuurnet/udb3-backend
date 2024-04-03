<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Movie;

final class MovieParser
{
    private array $termsMapper;

    private array $theatreMapper;
    public function __construct(
        array $termsMapper,
        array $theatreMapper
    ) {
        $this->termsMapper = $termsMapper;
        $this->theatreMapper = $theatreMapper;
    }

    public function parse(array $movieDetail): array
    {
        // Some movies do not have a length in the external API,
        // so we put the endTime equal to the startTime as a fallback.
        $length = $movieDetail['length'] ?? 0;
        $dates = $movieDetail['dates'];
        $dateFactory = new DateConverter();
        $screeningsByTheatre = $dateFactory->processDates($dates, $length);
        return $screeningsByTheatre;
    }

    public function getLocationId(string $tid): string
    {
        return $this->theatreMapper[$tid];
    }

    public function getThemeId(array $genreIds): string
    {
        // This is a best effort to match the External categorization to the a valid theme in the Publiq Taxonomy
        // A Match is not guaranteed
        foreach ($genreIds as $genreId) {
            if (array_key_exists($genreId, $this->termsMapper)) {
                return $this->termsMapper[$genreId];
            }
        }
        return '1.7.14.0.0'; // Use "Meerdere filmgenres" as a fallback
    }

    public function generateMovieId(int $mid, string $tid, string $version): string
    {
        $v = $version === '3D' ? 'v3D' : '';
        return 'Kinepolis:' . 't' . $tid . 'm' . $mid . $v;
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Kinepolis;

interface Parser
{
    /**
     * @param ParsedPriceForATheater[] $parsedPrices
     * @return ParsedMovie[]
     */
    public function getParsedMovies(array $moviesToParse, array $parsedPrices): array;
}

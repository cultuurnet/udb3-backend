<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Kinepolis\Parser;

use CultuurNet\UDB3\Kinepolis\ParsedMovie;
use CultuurNet\UDB3\Kinepolis\ParsedPriceForATheater;

interface Parser
{
    /**
     * @param ParsedPriceForATheater[] $parsedPrices
     * @return ParsedMovie[]
     */
    public function getParsedMovies(array $moviesToParse, array $parsedPrices): array;
}

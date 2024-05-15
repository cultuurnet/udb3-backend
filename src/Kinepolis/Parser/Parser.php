<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Kinepolis\Parser;

use CultuurNet\UDB3\Kinepolis\ValueObject\ParsedMovie;
use CultuurNet\UDB3\Kinepolis\ValueObject\ParsedPriceForATheater;

interface Parser
{
    /**
     * @param ParsedPriceForATheater[] $parsedPrices
     * @return ParsedMovie[]
     */
    public function getParsedMovies(array $moviesToParse, array $parsedPrices): array;
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Kinepolis;

interface Parser
{
    /**
     * @return ParsedMovie[]
     */
    public function getParsedMovies(array $moviesToParse): array;
}
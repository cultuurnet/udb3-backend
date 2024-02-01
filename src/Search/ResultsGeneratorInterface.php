<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

use CultuurNet\UDB3\Sorting;
use Generator;

interface ResultsGeneratorInterface
{
    public function count(string $query): int;

    public function search(string $query): Generator;

    public function getSorting(): Sorting;

    public function withSorting(Sorting $sorting): object;
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

use Generator;

interface ResultsGeneratorInterface
{
    public function count(string $query): int;

    public function search(string $query): Generator;
}

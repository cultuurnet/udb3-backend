<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

interface Sortable
{
    public function getSorting(): array;

    public function withSorting(array $sorting): object;
}

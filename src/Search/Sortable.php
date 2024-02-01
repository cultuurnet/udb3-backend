<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

use CultuurNet\UDB3\EventExport\Sorting;

interface Sortable
{
    public function getSorting(): Sorting;

    public function withSorting(Sorting $sorting): object;
}

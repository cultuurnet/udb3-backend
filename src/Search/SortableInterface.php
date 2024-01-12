<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Search;

use CultuurNet\UDB3\EventExport\SortOrder;

interface SortableInterface
{
    public function getSorting(): array;

    public function withSorting(array $sorting): SortableInterface;
}
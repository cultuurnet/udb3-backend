<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Command;

use CultuurNet\UDB3\EventExport\EventExportQuery;
use CultuurNet\UDB3\Search\Sorting;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;

interface ExportEventsInterface
{
    public function getQuery(): EventExportQuery;

    public function getAddress(): ?EmailAddress;

    /**
     * @return null|string[]
     */
    public function getSelection(): ?array;

    public function getSorting(): ?Sorting;

    public function withSorting(Sorting $sorting): object;
}

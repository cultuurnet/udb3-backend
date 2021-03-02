<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Command;

use CultuurNet\UDB3\EventExport\EventExportQuery;
use ValueObjects\Web\EmailAddress;

interface ExportEventsInterface
{
    /**
     * @return EventExportQuery The query.
     */
    public function getQuery(): EventExportQuery;


    public function getAddress(): ?EmailAddress;

    /**
     * @return null|string[]
     */
    public function getSelection(): ?array;
}

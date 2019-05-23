<?php

namespace CultuurNet\UDB3\EventExport\Command;

use CultuurNet\UDB3\EventExport\EventExportQuery;
use CultuurNet\UDB3\EventExport\SapiVersion;
use ValueObjects\Web\EmailAddress;

interface ExportEventsInterface
{
    /**
     * @return EventExportQuery The query.
     */
    public function getQuery(): EventExportQuery;

    /**
     * @return SapiVersion
     */
    public function getSapiVersion(): SapiVersion;

    /**
     * @return null|EmailAddress
     */
    public function getAddress(): ?EmailAddress;

    /**
     * @return null|\string[]
     */
    public function getSelection(): ?array;
}

<?php

namespace CultuurNet\UDB3\EventExport\Command;

use CultuurNet\UDB3\EventExport\EventExportQuery;
use ValueObjects\Web\EmailAddress;

class ExportEventsAsJsonLDJSONDeserializer extends ExportEventsJSONDeserializer
{
    /**
     * {@inheritdoc}
     */
    protected function createCommand(
        EventExportQuery $query,
        EmailAddress $address = null,
        $selection = null,
        $include = null
    ) {
        return new ExportEventsAsJsonLD(
            $query,
            $address,
            $selection,
            $include
        );
    }
}

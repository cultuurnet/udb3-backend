<?php

namespace CultuurNet\UDB3\EventExport\Command;

use CultuurNet\UDB3\EventExport\EventExportQuery;
use CultuurNet\UDB3\EventExport\SapiVersion;
use ValueObjects\Web\EmailAddress;

class ExportEventsAsJsonLDJSONDeserializer extends ExportEventsJSONDeserializer
{
    /**
     * {@inheritdoc}
     */
    protected function createCommand(
        EventExportQuery $query,
        SapiVersion $sapiVersion,
        EmailAddress $address = null,
        $selection = null,
        $include = null
    ) {
        return new ExportEventsAsJsonLD(
            $query,
            $sapiVersion,
            $address,
            $selection,
            $include
        );
    }
}

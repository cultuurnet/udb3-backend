<?php

namespace CultuurNet\UDB3\EventExport\Command;

use CultuurNet\UDB3\EventExport\EventExportQuery;
use CultuurNet\UDB3\EventExport\SapiVersion;
use ValueObjects\Web\EmailAddress;

class ExportEventsAsOOXMLJSONDeserializer extends ExportEventsJSONDeserializer
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
        return new ExportEventsAsOOXML(
            $query,
            $sapiVersion,
            $address,
            $selection,
            $include
        );
    }
}

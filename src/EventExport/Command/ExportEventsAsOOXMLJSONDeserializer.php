<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Command;

use CultuurNet\UDB3\EventExport\EventExportQuery;
use ValueObjects\Web\EmailAddress;

/**
 * @deprecated
 *   Refactor to implement RequestBodyParser and throw ApiProblemException
 */
class ExportEventsAsOOXMLJSONDeserializer extends ExportEventsJSONDeserializer
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
        return new ExportEventsAsOOXML(
            $query,
            $address,
            $selection,
            $include
        );
    }
}

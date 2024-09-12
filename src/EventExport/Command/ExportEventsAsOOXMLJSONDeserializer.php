<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Command;

use CultuurNet\UDB3\EventExport\EventExportQuery;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;

/**
 * @deprecated
 *   Refactor to implement RequestBodyParser and throw ApiProblemException
 */
class ExportEventsAsOOXMLJSONDeserializer extends ExportEventsJSONDeserializer
{
    protected function createCommand(
        EventExportQuery $query,
        ?array $include = null,
        EmailAddress $address = null,
        ?array $selection = null
    ): ExportEvents {
        return new ExportEventsAsOOXML(
            $query,
            $include,
            $address,
            $selection,
        );
    }
}

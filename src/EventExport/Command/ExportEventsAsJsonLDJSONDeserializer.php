<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Command;

use CultuurNet\UDB3\EventExport\EventExportQuery;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;

/**
 * @deprecated
 *   Refactor to implement RequestBodyParser and throw ApiProblemException
 */
class ExportEventsAsJsonLDJSONDeserializer extends ExportEventsJSONDeserializer
{
    protected function createCommand(
        EventExportQuery $query,
        $include,
        EmailAddress $address = null,
        $selection = null
    ): ExportEvents {
        return new ExportEventsAsJsonLD(
            $query,
            $include,
            $address,
            $selection,
        );
    }
}

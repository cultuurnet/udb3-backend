<?php

namespace CultuurNet\UDB3\EventExport;

use CultuurNet\UDB3\Offer\OfferIdentifierCollection;
use Psr\Log\LoggerInterface;
use ValueObjects\Web\EmailAddress;
use ValueObjects\Web\Url;

interface EventExportServiceInterface
{
    /**
     * @param FileFormatInterface $fileFormat
     *  The file format of the exported file.
     *
     * @param EventExportQuery $query
     *  The query that will be exported.
     *  A query has to be specified even if you are exporting a selection of events.
     *
     * @param EmailAddress|null $address
     *  An optional email address that will receive an email containing the exported file.
     *
     * @param LoggerInterface|null $logger
     *  An optional logger that reports unknown events and empty exports.
     *
     * @param string[]|null $selection
     *  A selection of items that will be included in the export.
     *  When left empty the whole query will export.
     *
     * @return bool|string
     *  The destination url of the export file or false if no events were found.
     */
    public function exportEvents(
        FileFormatInterface $fileFormat,
        EventExportQuery $query,
        EmailAddress $address = null,
        LoggerInterface $logger = null,
        $selection = null
    );
}

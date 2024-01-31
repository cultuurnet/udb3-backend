<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport;

use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use Psr\Log\LoggerInterface;

interface EventExportServiceInterface
{
    /**
     * @param FileFormatInterface  $fileFormat
     *  The file format of the exported file.
     *
     * @param EventExportQuery     $query
     *  The query that will be exported.
     *  A query has to be specified even if you are exporting a selection of events.
     *
     * @param EmailAddress|null    $address
     *  An optional email address that will receive an email containing the exported file.
     *
     * @param LoggerInterface|null $logger
     *  An optional logger that reports unknown events and empty exports.
     *
     * @param string[]|null        $selection
     *  A selection of items that will be included in the export.
     *  When left empty the whole query will export.
     *
     * @param Sorting|null $sorting
     *   An optional sorting order for the items that will be included in the export.
     *   @return bool|string
     *  The destination url of the export file or false if no events were found.
     *@link https://docs.publiq.be/docs/uitdatabank/search-api/sorting
     *
     */
    public function exportEvents(
        FileFormatInterface $fileFormat,
        EventExportQuery $query,
        EmailAddress $address = null,
        LoggerInterface $logger = null,
        ?array $selection = null,
        ?Sorting $sorting = null
    );
}

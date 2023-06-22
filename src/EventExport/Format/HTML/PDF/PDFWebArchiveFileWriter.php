<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\HTML\PDF;

use CultuurNet\UDB3\EventExport\CalendarSummary\CalendarSummaryRepositoryInterface;
use CultuurNet\UDB3\EventExport\Format\HTML\HTMLFileWriter;
use CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\EventInfo\EventInfoServiceInterface;
use CultuurNet\UDB3\EventExport\Format\HTML\WebArchive\WebArchiveFileWriter;
use PrinceXMLPhp\PrinceWrapper;

/**
 * Creates a PDF file of a an HTML export and all needed assets.
 */
class PDFWebArchiveFileWriter extends WebArchiveFileWriter
{
    protected PrinceWrapper $prince;

    public function __construct(
        string $princeXMLBinaryPath,
        HTMLFileWriter $htmlFileWriter,
        EventInfoServiceInterface $uitpas = null,
        CalendarSummaryRepositoryInterface $calendarSummaryRepository = null
    ) {
        parent::__construct($htmlFileWriter, $uitpas, $calendarSummaryRepository);
        $this->prince = new PrinceWrapper($princeXMLBinaryPath);
    }

    public function write(string $filePath, \Traversable $events): void
    {
        $originDirectory = $this->createWebArchiveDirectory($events);
        $originFile = $this->expandTmpPath($originDirectory) . '/index.html';

        $messages = [];
        $result = $this->prince->convert_file_to_file($originFile, $filePath, $messages);

        if (!$result) {
            $message = implode(PHP_EOL, $messages);
            throw new \RuntimeException($message);
        }

        $this->removeTemporaryArchiveDirectory($originDirectory);
    }
}

<?php

namespace CultuurNet\UDB3\EventExport;

use CultuurNet\UDB3\EventExport\Command\ExportEventsAsCSV;
use CultuurNet\UDB3\EventExport\Command\ExportEventsAsJsonLD;
use CultuurNet\UDB3\EventExport\Command\ExportEventsAsOOXML;
use CultuurNet\UDB3\EventExport\Command\ExportEventsAsPDF;
use CultuurNet\UDB3\EventExport\Format\HTML\WebArchive\WebArchiveTemplate;
use CultuurNet\UDB3\EventExport\Format\HTML\PDF\PDFWebArchiveFileFormat;
use CultuurNet\UDB3\EventExport\Format\HTML\Properties\Title;
use CultuurNet\UDB3\EventExport\Format\JSONLD\JSONLDFileFormat;
use CultuurNet\UDB3\EventExport\Format\TabularData\CSV\CSVFileFormat;
use CultuurNet\UDB3\EventExport\Format\TabularData\OOXML\OOXMLFileFormat;
use Psr\Log\LoggerInterface;
use ValueObjects\Web\EmailAddress;

class EventExportCommandHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EventExportServiceInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $eventExportService;

    /**
     * @var string
     */
    private $princeXMLBinaryPath;

    /**
     * @var EventExportCommandHandler
     */
    private $eventExportCommandHandler;

    /**
     * @var LoggerInterface
     */
    private $logger;

    protected function setUp(): void
    {

        $this->eventExportService = $this->createMock(EventExportServiceInterface::class);

        $eventExportServiceCollection = new EventExportServiceCollection();
        $eventExportServiceCollection = $eventExportServiceCollection->withService(
            new SapiVersion(SapiVersion::V2),
            $this->eventExportService
        );

        $this->princeXMLBinaryPath = 'PrinceXML path';

        $this->eventExportCommandHandler = new EventExportCommandHandler(
            $eventExportServiceCollection,
            $this->princeXMLBinaryPath
        );

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->eventExportCommandHandler->setLogger($this->logger);
    }

    /**
     * @test
     */
    public function it_handles_export_event_as_json_ld(): void
    {
        $exportEventsAsJsonLD = new ExportEventsAsJsonLD(
            new EventExportQuery('query'),
            new SapiVersion(SapiVersion::V2),
            new EmailAddress('jane@anonymous.com'),
            null,
            null
        );

        $this->eventExportService->expects($this->once())
            ->method('exportEvents')
            ->with(
                new JSONLDFileFormat($exportEventsAsJsonLD->getInclude()),
                new EventExportQuery('query'),
                new EmailAddress('jane@anonymous.com'),
                $this->logger,
                null
            );

        $this->eventExportCommandHandler->handle($exportEventsAsJsonLD);
    }

    /**
     * @test
     */
    public function it_handles_export_event_as_csv(): void
    {
        $exportEventsAsCSV = new ExportEventsAsCSV(
            new EventExportQuery('query'),
            new SapiVersion(SapiVersion::V2),
            new EmailAddress('jane@anonymous.com'),
            null,
            null
        );

        $this->eventExportService->expects($this->once())
            ->method('exportEvents')
            ->with(
                new CSVFileFormat($exportEventsAsCSV->getInclude()),
                new EventExportQuery('query'),
                new EmailAddress('jane@anonymous.com'),
                $this->logger,
                null
            );

        $this->eventExportCommandHandler->handle($exportEventsAsCSV);
    }

    /**
     * @test
     */
    public function it_handles_export_event_as_ooxml(): void
    {
        $exportEventsAsOOXML = new ExportEventsAsOOXML(
            new EventExportQuery('query'),
            new SapiVersion(SapiVersion::V2),
            new EmailAddress('jane@anonymous.com'),
            null,
            null
        );

        $this->eventExportService->expects($this->once())
            ->method('exportEvents')
            ->with(
                new OOXMLFileFormat($exportEventsAsOOXML->getInclude()),
                new EventExportQuery('query'),
                new EmailAddress('jane@anonymous.com'),
                $this->logger,
                null
            );

        $this->eventExportCommandHandler->handle($exportEventsAsOOXML);
    }

    /**
     * @test
     */
    public function it_handles_export_event_as_pdf(): void
    {
        $exportEventsAsPDF = new ExportEventsAsPDF(
            new EventExportQuery('query'),
            new SapiVersion(SapiVersion::V2),
            'brand',
            'logo',
            new Title('title'),
            WebArchiveTemplate::TIPS()
        );
        $exportEventsAsPDF = $exportEventsAsPDF->withEmailNotificationTo(
            new EmailAddress('jane@anonymous.com')
        );

        $this->eventExportService->expects($this->once())
            ->method('exportEvents')
            ->with(
                new PDFWebArchiveFileFormat(
                    $this->princeXMLBinaryPath,
                    WebArchiveTemplate::TIPS(),
                    'brand',
                    'logo',
                    new Title('title')
                ),
                new EventExportQuery('query'),
                new EmailAddress('jane@anonymous.com'),
                $this->logger,
                null
            );

        $this->eventExportCommandHandler->handle($exportEventsAsPDF);
    }
}

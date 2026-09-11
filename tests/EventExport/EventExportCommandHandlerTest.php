<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport;

use CultuurNet\UDB3\EventExport\CalendarSummary\CalendarSummaryRepositoryInterface;
use CultuurNet\UDB3\EventExport\Command\ExportEventsAsJsonLD;
use CultuurNet\UDB3\EventExport\Command\ExportEventsAsOOXML;
use CultuurNet\UDB3\EventExport\Command\ExportEventsAsPDF;
use CultuurNet\UDB3\EventExport\DeparturePlaces\DeparturePlaceResolver;
use CultuurNet\UDB3\EventExport\Format\HTML\WebArchive\WebArchiveTemplate;
use CultuurNet\UDB3\EventExport\Format\HTML\PDF\PDFWebArchiveFileFormat;
use CultuurNet\UDB3\EventExport\Format\HTML\Properties\Title;
use CultuurNet\UDB3\EventExport\Format\JSONLD\JSONLDFileFormat;
use CultuurNet\UDB3\EventExport\Format\TabularData\OOXML\OOXMLFileFormat;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class EventExportCommandHandlerTest extends TestCase
{
    private EventExportServiceInterface&MockObject $eventExportService;

    private string $princeXMLBinaryPath;

    private EventExportCommandHandler $eventExportCommandHandler;


    private LoggerInterface&MockObject $logger;

    private CalendarSummaryRepositoryInterface&MockObject $calendarSummary;

    protected function setUp(): void
    {
        $this->eventExportService = $this->createMock(EventExportServiceInterface::class);

        $this->princeXMLBinaryPath = 'PrinceXML path';

        $this->calendarSummary = $this->createMock(CalendarSummaryRepositoryInterface::class);

        $this->eventExportCommandHandler = new EventExportCommandHandler(
            $this->eventExportService,
            $this->princeXMLBinaryPath,
            $this->calendarSummary
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
            ['calendarSummary'],
            new EmailAddress('jane@anonymous.com'),
            null
        );

        $this->eventExportService->expects($this->once())
            ->method('exportEvents')
            ->with(
                new JSONLDFileFormat($exportEventsAsJsonLD->getInclude(), $this->calendarSummary),
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
    public function it_handles_export_event_as_ooxml(): void
    {
        $exportEventsAsOOXML = new ExportEventsAsOOXML(
            new EventExportQuery('query'),
            ['calendarSummary'],
            new EmailAddress('jane@anonymous.com'),
            null
        );

        $this->eventExportService->expects($this->once())
            ->method('exportEvents')
            ->with(
                new OOXMLFileFormat($exportEventsAsOOXML->getInclude(), null, $this->calendarSummary),
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
    public function it_starts_every_ooxml_export_with_an_empty_departure_place_memo(): void
    {
        $placeRepository = $this->createMock(DocumentRepository::class);
        $placeRepository->expects($this->exactly(2))->method('fetch')->willReturn(
            new JsonDocument('abc-123', Json::encode(['name' => ['nl' => 'Centraal Station']]))
        );

        $departurePlaceResolver = new DeparturePlaceResolver($placeRepository);

        $eventExportCommandHandler = new EventExportCommandHandler(
            $this->eventExportService,
            $this->princeXMLBinaryPath,
            $this->calendarSummary,
            null,
            null,
            $departurePlaceResolver
        );
        $eventExportCommandHandler->setLogger($this->logger);

        $placeUrls = ['https://io.uitdatabank.be/place/abc-123'];

        $departurePlaceResolver->resolve($placeUrls);

        $eventExportCommandHandler->handle(
            new ExportEventsAsOOXML(
                new EventExportQuery('query'),
                ['departurePlaces'],
                new EmailAddress('jane@anonymous.com'),
                null
            )
        );

        $departurePlaceResolver->resolve($placeUrls);
    }

    /**
     * @test
     */
    public function it_handles_export_event_as_pdf(): void
    {
        $exportEventsAsPDF = new ExportEventsAsPDF(
            new EventExportQuery('query'),
            'brand',
            'logo',
            new Title('title'),
            WebArchiveTemplate::tips()
        );
        $exportEventsAsPDF = $exportEventsAsPDF->withEmailNotificationTo(
            new EmailAddress('jane@anonymous.com')
        );

        $this->eventExportService->expects($this->once())
            ->method('exportEvents')
            ->with(
                new PDFWebArchiveFileFormat(
                    $this->princeXMLBinaryPath,
                    WebArchiveTemplate::tips(),
                    'brand',
                    'logo',
                    'title',
                    null,
                    null,
                    null,
                    null,
                    $this->calendarSummary
                ),
                new EventExportQuery('query'),
                new EmailAddress('jane@anonymous.com'),
                $this->logger,
                null
            );

        $this->eventExportCommandHandler->handle($exportEventsAsPDF);
    }
}

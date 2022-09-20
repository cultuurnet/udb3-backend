<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport;

use Broadway\CommandHandling\SimpleCommandHandler;
use CultuurNet\UDB3\EventExport\CalendarSummary\CalendarSummaryRepositoryInterface;
use CultuurNet\UDB3\EventExport\Command\ExportEventsAsJsonLD;
use CultuurNet\UDB3\EventExport\Command\ExportEventsAsOOXML;
use CultuurNet\UDB3\EventExport\Command\ExportEventsAsPDF;
use CultuurNet\UDB3\EventExport\Format\HTML\PDF\PDFWebArchiveFileFormat;
use CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\EventInfo\EventInfoServiceInterface;
use CultuurNet\UDB3\EventExport\Format\JSONLD\JSONLDFileFormat;
use CultuurNet\UDB3\EventExport\Format\TabularData\OOXML\OOXMLFileFormat;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Twig_Environment;

class EventExportCommandHandler extends SimpleCommandHandler implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected EventExportServiceInterface $eventExportService;

    protected string $princeXMLBinaryPath;

    protected ?EventInfoServiceInterface $uitpas;

    protected CalendarSummaryRepositoryInterface $calendarSummaryRepository;

    private ?Twig_Environment $twig;

    public function __construct(
        EventExportServiceInterface $eventExportService,
        string $princeXMLBinaryPath,
        CalendarSummaryRepositoryInterface $calendarSummaryRepository,
        EventInfoServiceInterface $uitpas = null,
        Twig_Environment $twig = null
    ) {
        $this->eventExportService = $eventExportService;
        $this->princeXMLBinaryPath = $princeXMLBinaryPath;
        $this->calendarSummaryRepository = $calendarSummaryRepository;
        $this->uitpas = $uitpas;
        $this->twig = $twig;
    }

    public function handleExportEventsAsJsonLD(
        ExportEventsAsJsonLD $exportCommand
    ): void {
        $this->eventExportService->exportEvents(
            new JSONLDFileFormat(
                $exportCommand->getInclude(),
                $this->calendarSummaryRepository
            ),
            $exportCommand->getQuery(),
            $exportCommand->getAddress(),
            $this->logger,
            $exportCommand->getSelection()
        );
    }

    public function handleExportEventsAsOOXML(
        ExportEventsAsOOXML $exportCommand
    ): void {
        $this->eventExportService->exportEvents(
            new OOXMLFileFormat(
                $exportCommand->getInclude(),
                $this->uitpas,
                $this->calendarSummaryRepository
            ),
            $exportCommand->getQuery(),
            $exportCommand->getAddress(),
            $this->logger,
            $exportCommand->getSelection()
        );
    }

    public function handleExportEventsAsPDF(
        ExportEventsAsPDF $exportCommand
    ): void {
        $fileFormat = new PDFWebArchiveFileFormat(
            $this->princeXMLBinaryPath,
            $exportCommand->getTemplate(),
            $exportCommand->getBrand(),
            $exportCommand->getLogo(),
            $exportCommand->getTitle()->toNative(),
            $exportCommand->getSubtitle() ? $exportCommand->getSubtitle()->toNative() : null,
            $exportCommand->getFooter() ? $exportCommand->getFooter()->toNative() : null,
            $exportCommand->getPublisher() ? $exportCommand->getPublisher()->toNative() : null,
            $this->uitpas,
            $this->calendarSummaryRepository,
            $this->twig
        );

        $this->eventExportService->exportEvents(
            $fileFormat,
            $exportCommand->getQuery(),
            $exportCommand->getAddress(),
            $this->logger,
            $exportCommand->getSelection()
        );
    }
}

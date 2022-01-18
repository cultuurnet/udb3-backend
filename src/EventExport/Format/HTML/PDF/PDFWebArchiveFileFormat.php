<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\HTML\PDF;

use CultuurNet\UDB3\EventExport\CalendarSummary\CalendarSummaryRepositoryInterface;
use CultuurNet\UDB3\EventExport\FileFormatInterface;
use CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\EventInfo\EventInfoServiceInterface;
use CultuurNet\UDB3\EventExport\Format\HTML\WebArchive\WebArchiveFileFormat;
use CultuurNet\UDB3\EventExport\Format\HTML\WebArchive\WebArchiveTemplate;
use Twig_Environment;

class PDFWebArchiveFileFormat extends WebArchiveFileFormat implements FileFormatInterface
{
    private string $princeXMLBinaryPath;

    private ?EventInfoServiceInterface $uitpas;

    private ?CalendarSummaryRepositoryInterface $calendarSummaryRepository;

    public function __construct(
        $princeXMLBinaryPath,
        WebArchiveTemplate $template,
        string $brand,
        string $logo,
        string $title,
        ?string $subTitle = null,
        ?string $footer = null,
        ?string $publisher = null,
        ?EventInfoServiceInterface $uitpas = null,
        ?CalendarSummaryRepositoryInterface $calendarSummaryRepository = null,
        ?Twig_Environment $twig = null
    ) {
        parent::__construct($template, $brand, $logo, $title, $subTitle, $footer, $publisher, $twig);
        $this->princeXMLBinaryPath = $princeXMLBinaryPath;
        $this->uitpas = $uitpas;
        $this->calendarSummaryRepository = $calendarSummaryRepository;
    }

    public function getFileNameExtension(): string
    {
        return 'pdf';
    }

    public function getWriter(): PDFWebArchiveFileWriter
    {
        return new PDFWebArchiveFileWriter(
            $this->princeXMLBinaryPath,
            $this->getHTMLFileWriter(),
            $this->uitpas,
            $this->calendarSummaryRepository
        );
    }
}

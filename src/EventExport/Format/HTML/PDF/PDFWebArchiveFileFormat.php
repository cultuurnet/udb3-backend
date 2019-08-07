<?php

namespace CultuurNet\UDB3\EventExport\Format\HTML\PDF;

use CultuurNet\UDB3\EventExport\CalendarSummary\CalendarSummaryRepositoryInterface;
use CultuurNet\UDB3\EventExport\FileFormatInterface;
use CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\EventInfo\EventInfoServiceInterface;
use CultuurNet\UDB3\EventExport\Format\HTML\WebArchive\WebArchiveFileFormat;
use CultuurNet\UDB3\EventExport\Format\HTML\WebArchive\WebArchiveTemplate;
use Twig_Environment;

class PDFWebArchiveFileFormat extends WebArchiveFileFormat implements FileFormatInterface
{
    /**
     * @var string
     */
    protected $princeXMLBinaryPath;

    /**
     * @var EventInfoServiceInterface
     */
    protected $uitpas;

    /**
     * @var CalendarSummaryRepositoryInterface
     */
    protected $calendarSummaryRepository;

    /**
     * @param string                                  $princeXMLBinaryPath
     * @param WebArchiveTemplate                      $template
     * @param string                                  $brand
     * @param string                                  $logo
     * @param string                                  $title
     * @param string|null                             $subTitle
     * @param string|null                             $footer
     * @param string|null                             $publisher
     * @param EventInfoServiceInterface|null          $uitpas
     * @param CalendarSummaryRepositoryInterface|null $calendarSummaryRepository
     * @param Twig_Environment|null                   $twig
     */
    public function __construct(
        $princeXMLBinaryPath,
        WebArchiveTemplate $template,
        $brand,
        $logo,
        $title,
        $subTitle = null,
        $footer = null,
        $publisher = null,
        EventInfoServiceInterface $uitpas = null,
        CalendarSummaryRepositoryInterface $calendarSummaryRepository = null,
        Twig_Environment $twig = null
    ) {
        parent::__construct($template, $brand, $logo, $title, $subTitle, $footer, $publisher, $twig);
        $this->princeXMLBinaryPath = $princeXMLBinaryPath;
        $this->uitpas = $uitpas;
        $this->calendarSummaryRepository = $calendarSummaryRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function getFileNameExtension()
    {
        return 'pdf';
    }

    /**
     * {@inheritdoc}
     */
    public function getWriter()
    {
        return new PDFWebArchiveFileWriter(
            $this->princeXMLBinaryPath,
            $this->getHTMLFileWriter(),
            $this->uitpas,
            $this->calendarSummaryRepository
        );
    }
}

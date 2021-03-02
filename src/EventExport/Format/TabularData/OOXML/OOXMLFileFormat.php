<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\TabularData\OOXML;

use CultuurNet\UDB3\EventExport\CalendarSummary\CalendarSummaryRepositoryInterface;
use CultuurNet\UDB3\EventExport\FileFormatInterface;
use CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\EventInfo\EventInfoServiceInterface;
use CultuurNet\UDB3\EventExport\Format\TabularData\TabularDataFileWriter;

class OOXMLFileFormat implements FileFormatInterface
{
    /**
     * @var string[]
     */
    protected $include;

    /**
     * @var EventInfoServiceInterface;
     */
    protected $uitpas;

    /**
     * @var CalendarSummaryRepositoryInterface|null
     */
    protected $calendarSummaryRepository;

    /**
     * @param string[]|null                      $include
     * @param CalendarSummaryRepositoryInterface $calendarSummaryRepository
     */
    public function __construct(
        $include = null,
        EventInfoServiceInterface $uitpas = null,
        CalendarSummaryRepositoryInterface $calendarSummaryRepository = null
    ) {
        $this->include = $include;
        $this->uitpas = $uitpas;
        $this->calendarSummaryRepository = $calendarSummaryRepository;
    }

    /**
     * @inheritdoc
     */
    public function getFileNameExtension()
    {
        return 'xlsx';
    }

    /**
     * @inheritdoc
     */
    public function getWriter()
    {
        return new TabularDataFileWriter(
            new OOXMLFileWriterFactory(),
            $this->include,
            $this->uitpas,
            $this->calendarSummaryRepository
        );
    }
}

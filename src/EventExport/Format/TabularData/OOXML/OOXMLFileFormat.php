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
    private ?array $include;

    private ?EventInfoServiceInterface $uitpas;


    protected ?CalendarSummaryRepositoryInterface $calendarSummaryRepository;

    /**
     * @param string[]|null                      $include
     */
    public function __construct(
        ?array $include = null,
        ?EventInfoServiceInterface $uitpas = null,
        ?CalendarSummaryRepositoryInterface $calendarSummaryRepository = null
    ) {
        $this->include = $include;
        $this->uitpas = $uitpas;
        $this->calendarSummaryRepository = $calendarSummaryRepository;
    }

    public function getFileNameExtension(): string
    {
        return 'xlsx';
    }

    public function getWriter(): TabularDataFileWriter
    {
        return new TabularDataFileWriter(
            new OOXMLFileWriterFactory(),
            $this->include,
            $this->uitpas,
            $this->calendarSummaryRepository
        );
    }
}

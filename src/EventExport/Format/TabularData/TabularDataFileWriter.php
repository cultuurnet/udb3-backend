<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\TabularData;

use CultuurNet\UDB3\EventExport\CalendarSummary\CalendarSummaryRepositoryInterface;
use CultuurNet\UDB3\EventExport\FileWriterInterface;
use CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\EventInfo\EventInfoServiceInterface;

class TabularDataFileWriter implements FileWriterInterface
{
    protected TabularDataEventFormatter $eventFormatter;

    protected TabularDataFileWriterFactoryInterface $tabularDataFileWriterFactory;

    /**
     * @param string[] $include
     */
    public function __construct(
        TabularDataFileWriterFactoryInterface $tabularDataFileWriterFactory,
        array $include,
        EventInfoServiceInterface $uitpas = null,
        CalendarSummaryRepositoryInterface $calendarSummaryRepository = null
    ) {
        $this->tabularDataFileWriterFactory = $tabularDataFileWriterFactory;
        $this->eventFormatter = new TabularDataEventFormatter($include, $uitpas, $calendarSummaryRepository);
    }

    protected function writeHeader(TabularDataFileWriterInterface $tabularDataFileWriter): void
    {
        $headerRow = $this->eventFormatter->formatHeader();

        $tabularDataFileWriter->writeRow($headerRow);
    }

    public function write(string $filePath, \Traversable $events): void
    {
        $tabularDataFileWriter = $this->openFileWriter($filePath);

        $this->writeHeader($tabularDataFileWriter);
        $this->writeEvents($tabularDataFileWriter, $events);

        $tabularDataFileWriter->close();
    }

    protected function writeEvents(
        TabularDataFileWriterInterface $tabularDataFileWriter,
        \Traversable $events
    ): void {
        foreach ($events as $event) {
            $eventRow = $this->eventFormatter->formatEvent($event);
            $tabularDataFileWriter->writeRow($eventRow);
        }
    }

    protected function openFileWriter(string $filePath): TabularDataFileWriterInterface
    {
        return $this->tabularDataFileWriterFactory->openTabularDataFileWriter(
            $filePath
        );
    }
}

<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\TabularData;

use CultuurNet\UDB3\EventExport\CalendarSummary\CalendarSummaryRepositoryInterface;
use CultuurNet\UDB3\EventExport\FileWriterInterface;
use CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\EventInfo\EventInfoServiceInterface;

class TabularDataFileWriter implements FileWriterInterface
{
    /**
     * @var TabularDataEventFormatter
     */
    protected $eventFormatter;

    /**
     * @var TabularDataFileWriterFactoryInterface
     */
    protected $tabularDataFileWriterFactory;

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


    protected function writeHeader(TabularDataFileWriterInterface $tabularDataFileWriter)
    {
        $headerRow = $this->eventFormatter->formatHeader();

        $tabularDataFileWriter->writeRow($headerRow);
    }

    /**
     * {@inheritdoc}
     */
    public function write($filePath, $events)
    {
        $tabularDataFileWriter = $this->openFileWriter($filePath);

        $this->writeHeader($tabularDataFileWriter);
        $this->writeEvents($tabularDataFileWriter, $events);

        $tabularDataFileWriter->close();
    }

    /**
     * @param \Traversable                   $events
     */
    protected function writeEvents(
        TabularDataFileWriterInterface $tabularDataFileWriter,
        $events
    ) {
        foreach ($events as $event) {
            $eventRow = $this->eventFormatter->formatEvent($event);
            $tabularDataFileWriter->writeRow($eventRow);
        }
    }

    /**
     * @param string $filePath
     * @return TabularDataFileWriterInterface
     */
    protected function openFileWriter($filePath)
    {
        return $this->tabularDataFileWriterFactory->openTabularDataFileWriter(
            $filePath
        );
    }
}

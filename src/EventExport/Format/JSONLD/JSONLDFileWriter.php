<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\JSONLD;

use CultuurNet\UDB3\EventExport\CalendarSummary\CalendarSummaryRepositoryInterface;
use CultuurNet\UDB3\EventExport\FileWriterInterface;

final class JSONLDFileWriter implements FileWriterInterface
{
    private JSONLDEventFormatter $eventFormatter;

    public function __construct($include = null, CalendarSummaryRepositoryInterface $calendarSummaryRepository)
    {
        $this->eventFormatter = new JSONLDEventFormatter($include, $calendarSummaryRepository);
    }

    /**
     * @return Resource
     */
    private function openFile(string $filePath)
    {
        $file = fopen($filePath, 'w');
        if (false === $file) {
            throw new \RuntimeException(
                'Unable to open file for writing: ' . $filePath
            );
        }

        return $file;
    }

    /**
     * {@inheritdoc}
     */
    public function write($filePath, $events): void
    {
        $file = $this->openFile($filePath);

        fwrite($file, '[');

        $this->writeEvents($file, $events);

        fwrite($file, ']');

        fclose($file);
    }

    /**
     * @param Resource $file
     */
    private function writeEvents($file, \Traversable $events): void
    {
        $first = true;

        foreach ($events as $event) {
            if ($first) {
                $first = false;
            } else {
                fwrite($file, ',');
            }

            $formattedEvent = $this->eventFormatter->formatEvent($event);

            fwrite($file, $formattedEvent);
        }
    }
}

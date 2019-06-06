<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\EventExport\Format\JSONLD;

use CultuurNet\UDB3\EventExport\FileWriterInterface;

class JSONLDFileWriter implements FileWriterInterface
{
    /**
     * @var JSONLDEventFormatter
     */
    protected $eventFormatter;

    public function __construct($include = null)
    {
        $this->eventFormatter = new JSONLDEventFormatter($include);
    }

    /**
     * @param string $filePath
     * @return Resource
     */
    protected function openFile($filePath)
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
    public function write($filePath, $events)
    {
        $file = $this->openFile($filePath);

        fwrite($file, '[');

        $this->writeEvents($file, $events);

        fwrite($file, ']');

        fclose($file);
    }

    /**
     * @param Resource $file
     * @param \Traversable $events
     */
    protected function writeEvents($file, $events)
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

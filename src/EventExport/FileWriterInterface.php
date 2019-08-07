<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\EventExport;

interface FileWriterInterface
{
    /**
     * @param string $filePath
     * @param \Traversable $events
     * @return void
     */
    public function write($filePath, $events);
}

<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\EventExport\Format\TabularData;

interface TabularDataFileWriterFactoryInterface
{
    /**
     * @return TabularDataFileWriterInterface
     * @param string $filePath
     */
    public function openTabularDataFileWriter($filePath);
}

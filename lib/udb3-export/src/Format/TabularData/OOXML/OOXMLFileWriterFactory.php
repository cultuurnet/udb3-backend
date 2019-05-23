<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\EventExport\Format\TabularData\OOXML;

use CultuurNet\UDB3\EventExport\Format\TabularData\TabularDataFileWriterFactoryInterface;

class OOXMLFileWriterFactory implements TabularDataFileWriterFactoryInterface
{
    /**
     * @return \CultuurNet\UDB3\EventExport\Format\TabularData\TabularDataFileWriterInterface
     * @param string $filePath
     */
    public function openTabularDataFileWriter($filePath)
    {
        return new OOXMLFileWriter($filePath);
    }
}

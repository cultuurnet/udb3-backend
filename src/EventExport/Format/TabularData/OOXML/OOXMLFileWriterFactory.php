<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\TabularData\OOXML;

use CultuurNet\UDB3\EventExport\Format\TabularData\TabularDataFileWriterFactoryInterface;
use CultuurNet\UDB3\EventExport\Format\TabularData\TabularDataFileWriterInterface;

class OOXMLFileWriterFactory implements TabularDataFileWriterFactoryInterface
{
    /**
     * @param int[] $wrappedColumns
     */
    public function openTabularDataFileWriter(
        string $filePath,
        array $wrappedColumns = []
    ): TabularDataFileWriterInterface {
        return new OOXMLFileWriter($filePath, $wrappedColumns);
    }
}

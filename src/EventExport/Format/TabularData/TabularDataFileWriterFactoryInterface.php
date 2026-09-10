<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\TabularData;

interface TabularDataFileWriterFactoryInterface
{
    /**
     * @param int[] $wrappedColumns
     */
    public function openTabularDataFileWriter(
        string $filePath,
        array $wrappedColumns = []
    ): TabularDataFileWriterInterface;
}

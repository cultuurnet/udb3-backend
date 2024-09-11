<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\TabularData;

interface TabularDataFileWriterFactoryInterface
{
    public function openTabularDataFileWriter(string $filePath): TabularDataFileWriterInterface;
}

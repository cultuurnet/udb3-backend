<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport;

interface FileWriterInterface
{
    public function write(string $filePath, \Traversable $events): void;
}

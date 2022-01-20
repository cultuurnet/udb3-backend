<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport;

interface FileFormatInterface
{
    /**
     * Gets the file name extension applicable to the file format.
     * @link http://en.wikipedia.org/wiki/Filename_extension
     */
    public function getFileNameExtension(): string;

    /**
     * Opens a file for exporting data to it.
     */
    public function getWriter(): FileWriterInterface;
}

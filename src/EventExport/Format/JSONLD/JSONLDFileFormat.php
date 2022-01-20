<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\JSONLD;

use CultuurNet\UDB3\EventExport\FileFormatInterface;

class JSONLDFileFormat implements FileFormatInterface
{
    /**
     * @var string[]
     */
    protected ?array $include;

    /**
     * @param null|string[] $include
     */
    public function __construct(?array $include = null)
    {
        $this->include = $include;
    }

    public function getFileNameExtension(): string
    {
        return 'json';
    }

    public function getWriter(): JSONLDFileWriter
    {
        return new JSONLDFileWriter($this->include);
    }
}

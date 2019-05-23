<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\EventExport\Format\JSONLD;

use CultuurNet\UDB3\EventExport\FileFormatInterface;

class JSONLDFileFormat implements FileFormatInterface
{
    /**
     * @var string[]
     */
    protected $include;

    /**
     * @param string[] $include
     */
    public function __construct($include = null)
    {
        $this->include = $include;
    }

    /**
     * @inheritdoc
     */
    public function getFileNameExtension()
    {
        return 'json';
    }

    /**
     * @inheritdoc
     */
    public function getWriter()
    {
        return new JSONLDFileWriter($this->include);
    }
}

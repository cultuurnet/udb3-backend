<?php

namespace CultuurNet\UDB3\UDB2\XSD;

class FileGetContentsXSDReader implements XSDReaderInterface
{
    /**
     * @var string
     */
    private $fileLocation;

    /**
     * @param string $fileLocation
     */
    public function __construct($fileLocation)
    {
        $this->fileLocation = (string) $fileLocation;
    }

    /**
     * @return XSD
     */
    public function read()
    {
        $content = @file_get_contents($this->fileLocation);

        if (!$content) {
            throw new \RuntimeException('Could not read XSD file from ' . $this->fileLocation);
        }

        return new XSD($content);
    }
}

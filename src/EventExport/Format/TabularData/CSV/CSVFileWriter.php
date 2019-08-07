<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\EventExport\Format\TabularData\CSV;

use CultuurNet\UDB3\EventExport\Format\TabularData\TabularDataFileWriterInterface;

class CSVFileWriter implements TabularDataFileWriterInterface
{
    protected $f;

    protected $delimiter;

    protected $includedProperties;

    public function __construct($filePath)
    {
        $this->f = fopen($filePath, 'w');
        if (false === $this->f) {
            throw new \RuntimeException('Unable to open file for writing: ' . $filePath);
        }

        $this->delimiter = ',';

        // Overwrite default Excel delimiter.
        // UTF-16LE BOM
        fwrite($this->f, "\xFF\xFE");
        fwrite($this->f, "sep={$this->delimiter}");
        fwrite($this->f, PHP_EOL);

        $this->first = true;
    }

    public function writeRow($data)
    {
        foreach ($data as $key => $value) {
            $data[$key] = iconv('UTF-8', 'UTF-16LE//IGNORE', $value);
        }

        fputcsv($this->f, $data, $this->delimiter);
    }

    public function close()
    {
        if (is_resource($this->f)) {
            fclose($this->f);
        }
    }
}

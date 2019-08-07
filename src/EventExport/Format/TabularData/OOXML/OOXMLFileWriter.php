<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\EventExport\Format\TabularData\OOXML;

use CultuurNet\UDB3\EventExport\Format\TabularData\TabularDataFileWriterInterface;

class OOXMLFileWriter implements TabularDataFileWriterInterface
{
    /**
     * @var string
     */
    protected $filePath;

    /**
     * Next row number to write to.
     *
     * @var int
     */
    protected $i;

    /**
     * @param $filePath
     */
    public function __construct($filePath)
    {
        $this->filePath = $filePath;

        $this->spreadsheet = new \PHPExcel();

        $this->spreadsheet->setActiveSheetIndex(0);

        $this->i = 1;
    }

    /**
     * @param string[] $row
     */
    public function writeRow($row)
    {
        $this->spreadsheet->getActiveSheet()->fromArray(
            $row,
            '',
            'A' . $this->i
        );

        $this->i++;
    }

    /**
     * @return void
     */
    public function close()
    {
        $objWriter = new \PHPExcel_Writer_Excel2007($this->spreadsheet);
        $objWriter->save($this->filePath);
    }
}

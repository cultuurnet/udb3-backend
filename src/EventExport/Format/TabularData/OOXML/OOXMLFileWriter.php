<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\TabularData\OOXML;

use CultuurNet\UDB3\EventExport\Format\TabularData\TabularDataFileWriterInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class OOXMLFileWriter implements TabularDataFileWriterInterface
{
    private string $filePath;

    private int $i;

    private Spreadsheet $spreadsheet;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
        $this->spreadsheet = new Spreadsheet();
        $this->spreadsheet->setActiveSheetIndex(0);
        $this->i = 1;
    }

    /**
     * @param string[] $row
     */
    public function writeRow($row): void
    {
        $this->spreadsheet->getActiveSheet()->fromArray(
            $row,
            '',
            'A' . $this->i
        );

        $this->i++;
    }

    public function close(): void
    {
        $objWriter = new Xlsx($this->spreadsheet);
        $objWriter->save($this->filePath);
    }
}

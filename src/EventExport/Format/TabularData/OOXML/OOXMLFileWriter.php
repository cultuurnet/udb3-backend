<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\TabularData\OOXML;

use CultuurNet\UDB3\EventExport\Format\TabularData\TabularDataFileWriterInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class OOXMLFileWriter implements TabularDataFileWriterInterface
{
    /**
     * Text only wraps inside the width of its column, and the default of about 8 characters would
     * turn every description into a column of single words, so all columns are widened.
     */
    private const COLUMN_WIDTH = 40;

    private string $filePath;

    private int $i;

    private Spreadsheet $spreadsheet;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
        $this->spreadsheet = new Spreadsheet();
        $this->spreadsheet->setActiveSheetIndex(0);
        $this->i = 1;

        // Columns like the description and the FAQ hold long text with newlines in it, which Excel
        // only shows as line breaks when the cell wraps its text. Rows keep their automatic height,
        // so they grow to fit whatever a cell wraps to.
        $alignment = $this->spreadsheet->getDefaultStyle()->getAlignment();
        $alignment->setWrapText(true);
        $alignment->setVertical(Alignment::VERTICAL_TOP);
    }

    /**
     * @param string[] $row
     */
    public function writeRow($row): void
    {
        // The header is the first row that is written, so it decides how many columns to widen.
        if ($this->i === 1) {
            $this->widenColumns(count($row));
        }

        $this->spreadsheet->getActiveSheet()->fromArray(
            $row,
            '',
            'A' . $this->i
        );

        $this->i++;
    }

    private function widenColumns(int $columnCount): void
    {
        $sheet = $this->spreadsheet->getActiveSheet();

        for ($column = 1; $column <= $columnCount; $column++) {
            $sheet->getColumnDimensionByColumn($column)->setWidth(self::COLUMN_WIDTH);
        }
    }

    public function close(): void
    {
        $objWriter = new Xlsx($this->spreadsheet);
        $objWriter->save($this->filePath);
    }
}
